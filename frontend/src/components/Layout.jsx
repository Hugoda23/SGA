import { useState, useEffect, useRef, Suspense } from 'react'
import { NavLink, Link, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'
import { badge } from '../lib/twClasses'

const navItems = [
  { label: 'Dashboard', path: '/', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1', roles: ['admin'], permiso: 'dashboard.ver' },
  { label: 'Mi Resumen', path: '/', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1', roles: ['director', 'secretaria', 'alumno'] },
  { label: 'Usuarios', path: '/admin/usuarios', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', roles: ['admin'], permiso: 'usuarios.ver' },
  { label: 'Cursos', path: '/cursos', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', roles: ['admin', 'director'], permiso: 'cursos.ver' },
  {
    label: 'Infraestructura',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    roles: ['admin', 'director'],
    subItems: [
      { label: 'Edificios', path: '/edificios', permiso: 'edificios.ver' },
      { label: 'Aulas', path: '/aulas', permiso: 'aulas.ver' },
      { label: 'Grados', path: '/grados', permiso: 'grados.ver' },
      { label: 'Secciones', path: '/secciones', permiso: 'secciones.ver' },
    ]
  },
  {
    label: 'Reportes PDF',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    roles: ['admin', 'director'],
    permiso: 'reportes.ver',
    subItems: [
      { label: 'Actas', path: '/reportes/actas' },
      { label: 'Notas', path: '/reportes/notas' },
      { label: 'Constancias', path: '/reportes/constancias' },
      { label: 'Rendimiento', path: '/reportes/rendimiento' }
    ]
  },
  { label: 'Bitácora de Auditoría', path: '/auditoria', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', roles: ['admin'], permiso: 'bitacoras.ver' },
  { label: 'Logs del Sistema', path: '/admin/logs', icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', roles: ['admin'], permiso: 'logs.ver' },
  { label: 'Catedráticos', path: '/catedraticos', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', roles: ['admin', 'director', 'secretaria'], permiso: 'catedraticos.ver' },
  { label: 'Alumnos', path: '/alumnos', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', roles: ['admin', 'director', 'secretaria'], permiso: 'alumnos.ver' },
  { label: 'Carreras', path: '/carreras', icon: 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342', roles: ['admin', 'director'], permiso: 'carreras.ver' },
  { label: 'Periodos', path: '/periodos', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', roles: ['admin', 'director'], permiso: 'periodos.ver' },
  { label: 'Pensum', path: '/pensum', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', roles: ['admin', 'director'], permiso: 'pensum.ver' },
  { label: 'Asignaciones', path: '/asignaciones', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', roles: ['admin', 'director', 'secretaria'], permiso: 'asignaciones.ver' },
  { label: 'Inscripciones', path: '/inscripciones', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', roles: ['admin', 'director', 'secretaria'], permiso: 'inscripciones.ver' },
  { label: 'Roles', path: '/roles', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', roles: ['admin'], permiso: 'roles.ver' },
  { label: 'Permisos', path: '/permisos', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', roles: ['admin'], permiso: 'permisos.ver' },
  { label: 'Configuración', path: '/configuracion', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', roles: ['admin'], permiso: 'configuracion.ver' },
  { label: 'Notificaciones', path: '/notificaciones', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', roles: ['admin', 'director', 'secretaria', 'catedratico', 'alumno'] },
  { label: 'Historial Reportes', path: '/reportes-generados', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', roles: ['admin', 'director'], permiso: 'reportes.ver' },
  { label: 'Mis Tareas', path: '/mis-tareas', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', roles: ['alumno'] },
  { label: 'Mis Cursos', path: '/mis-cursos-alumno', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', roles: ['alumno'] },
  { label: 'Mi Horario', path: '/mi-horario', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', roles: ['alumno'] },
  { label: 'Entregas de Tareas', path: '/entregas-tarea', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', roles: ['catedratico'] },
  { label: 'Mis Cursos', path: '/mis-cursos', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', roles: ['catedratico'] },
  { label: 'Registro de Calificaciones', path: '/registro-calificaciones', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', roles: ['catedratico'] },
  { label: 'Control de Asistencia', path: '/asistencia', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', roles: ['catedratico'] },
  { label: 'Configuración de Curso', path: '/configuracion-curso', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', roles: ['catedratico'] },
]

function NavIcon({ path, className }) {
  return (
    <svg className={className || 'h-5 w-5'} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d={path} />
    </svg>
  )
}

export default function Layout() {
  const { user, logout, hasRole, hasPermiso } = useAuth()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [expandedMenus, setExpandedMenus] = useState({})
  const [notifCount, setNotifCount] = useState(0)
  const [notifDropdown, setNotifDropdown] = useState(false)
  const [notifList, setNotifList] = useState([])
  const [userDropdown, setUserDropdown] = useState(false)
  const notifRef = useRef(null)
  const userRef = useRef(null)
  const navigate = useNavigate()

  const fetchNotifCount = () => {
    api.get('/v1/notificaciones/no-leidas').then((r) => {
      setNotifCount(Array.isArray(r.data) ? r.data.length : 0)
    }).catch(() => {})
  }

  const fetchNotifList = () => {
    api.get('/v1/notificaciones/no-leidas').then((r) => {
      setNotifList(Array.isArray(r.data) ? r.data : [])
    }).catch(() => {})
  }

  useEffect(() => {
    fetchNotifCount()
    fetchNotifList()
  }, [])

  useEffect(() => {
    const interval = setInterval(fetchNotifCount, 30000)
    return () => clearInterval(interval)
  }, [])

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (notifRef.current && !notifRef.current.contains(e.target)) setNotifDropdown(false)
      if (userRef.current && !userRef.current.contains(e.target)) setUserDropdown(false)
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleMarcarLeido = async (id) => {
    try { await api.patch(`/v1/notificaciones/${id}/leido`); fetchNotifCount(); fetchNotifList() } catch {}
  }

  const handleMarcarTodasLeidas = async () => {
    try { await api.post('/v1/notificaciones/marcar-todas-leidas'); fetchNotifCount(); fetchNotifList() } catch {}
  }

  const formatNotifTime = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return ''
    const diffMin = Math.floor((Date.now() - date.getTime()) / 60000)
    if (diffMin < 1) return 'Ahora'
    if (diffMin < 60) return `Hace ${diffMin} min`
    const hours = Math.floor(diffMin / 60)
    if (hours < 24) return `Hace ${hours} h`
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
  }

  const toggleMenu = (label) => {
    setExpandedMenus(prev => ({ ...prev, [label]: !prev[label] }))
  }

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const visibleItems = navItems
    .filter((item) =>
      item.roles.some((r) => hasRole(r)) &&
      (!item.permiso || hasPermiso(item.permiso))
    )
    .map((item) => {
      if (!item.subItems) return item
      const subItems = item.subItems.filter((sub) => !sub.permiso || hasPermiso(sub.permiso))
      return subItems.length > 0 ? { ...item, subItems } : null
    })
    .filter(Boolean)

  const displayName = user?.alumno
    ? `${user.alumno.nombre} ${user.alumno.apellido}`.trim()
    : user?.catedratico
    ? `${user.catedratico.nombre} ${user.catedratico.apellido}`.trim()
    : user?.username || 'Alejandro Díaz'

  const userRoleStr = user?.roles?.[0]?.nombre
  const rolePrefix = userRoleStr === 'alumno' ? 'Alumno: ' : userRoleStr === 'catedratico' ? 'Catedrático: ' : 'Usuario: '
  const roleSublabel = userRoleStr === 'alumno' ? 'Estudiante' : userRoleStr === 'catedratico' ? 'Docente' : 'Administrador'

  const getInitials = (name) => {
    if (!name) return 'AA'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    return name.slice(0, 2).toUpperCase()
  }

  return (
    <div className="flex h-screen bg-neutral-100 font-sans">
      <aside className={`${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-surface-dark text-white shadow-lg transition-transform duration-200 lg:static lg:translate-x-0`}>
        <div className="flex items-center gap-3.5 border-b border-neutral-600/40 p-6">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-neutral-500/30 bg-neutral-800 font-mono text-sm font-bold text-neutral-200 shadow-2">
            [L]
          </div>
          <div>
            <h1 className="text-xl font-bold leading-tight tracking-tight">Inst.<br />Florencio</h1>
            <p className="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-neutral-400">Carrascoza</p>
          </div>
        </div>
        <div className="flex-1 overflow-y-auto px-4 py-4">
          <p className="mb-4 px-2 text-xs font-bold uppercase tracking-wider text-neutral-400">Menú Principal</p>
          <nav className="space-y-1">
            {visibleItems.map((item) => {
              if (item.subItems) {
                const isExpanded = expandedMenus[item.label]
                return (
                  <div key={item.label}>
                    <button
                      type="button"
                      data-twe-ripple-init
                      onClick={() => toggleMenu(item.label)}
                      className="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-300 transition-colors hover:bg-neutral-700/60 hover:text-white"
                    >
                      <div className="flex items-center gap-3">
                        <NavIcon path={item.icon} className="h-5 w-5 shrink-0" />
                        {item.label}
                      </div>
                      <svg className={`h-4 w-4 transition-transform ${isExpanded ? 'rotate-90' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    {isExpanded && (
                      <div className="mt-1 space-y-1 pl-10 pr-2">
                        {item.subItems.map(sub => (
                          <NavLink
                            key={sub.path}
                            to={sub.path}
                            onClick={() => setSidebarOpen(false)}
                            className={({ isActive }) =>
                              `block rounded-lg px-3 py-2 text-sm transition-colors ${isActive ? 'bg-[#1e2738] font-semibold text-white shadow-1' : 'text-neutral-400 hover:bg-neutral-700/60 hover:text-white'}`
                            }
                          >
                            {sub.label}
                          </NavLink>
                        ))}
                      </div>
                    )}
                  </div>
                )
              }

              return (
                <NavLink
                  key={item.path}
                  to={item.path}
                  end={item.path === '/'}
                  onClick={() => setSidebarOpen(false)}
                  className={({ isActive }) =>
                    `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${isActive ? 'bg-[#1e2738] font-bold text-white shadow-1' : 'text-neutral-300 hover:bg-neutral-700/60 hover:text-white'}`
                  }
                >
                  <NavIcon path={item.icon} className="h-5 w-5 shrink-0" />
                  {item.label}
                </NavLink>
              )
            })}
          </nav>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-10 flex items-center justify-between border-b border-neutral-200 bg-white px-6 py-3.5 shadow-sm">
          <div className="flex items-center gap-4 lg:hidden">
            <button type="button" onClick={() => setSidebarOpen(!sidebarOpen)} className="text-neutral-600">
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <h2 className="font-semibold text-neutral-800">SGA</h2>
          </div>
          <div className="hidden flex-1 lg:flex"></div>

          <div className="flex items-center gap-4 md:gap-6">
            <div className="relative" ref={notifRef}>
              <button
                type="button"
                data-twe-ripple-init
                onClick={() => { setNotifDropdown(!notifDropdown); if (!notifDropdown) fetchNotifList() }}
                aria-label="Notificaciones"
                title="Notificaciones"
                className="relative inline-flex h-10 w-10 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 shadow-2 transition duration-150 ease-in-out hover:bg-primary-100 hover:text-primary focus:outline-none active:bg-primary-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
              >
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {notifCount > 0 && (
                  <span className="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white shadow-danger-3 dark:shadow-black/30">
                    {notifCount > 9 ? '9+' : notifCount}
                  </span>
                )}
              </button>
              {notifDropdown && (
                <div className="absolute right-0 top-full z-50 mt-3 w-80 overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark dark:border dark:border-neutral-700">
                  <div className="flex items-center justify-between border-b-2 border-neutral-100 p-4 dark:border-neutral-700">
                    <span className="text-sm font-bold text-neutral-800 dark:text-neutral-100">Notificaciones</span>
                    <div className="flex items-center gap-3">
                      {notifCount > 0 && (
                        <button
                          type="button"
                          data-twe-ripple-init
                          onClick={handleMarcarTodasLeidas}
                          className="text-xs font-bold text-primary transition hover:text-primary-accent-300"
                        >
                          Marcar todas
                        </button>
                      )}
                      <button
                        type="button"
                        data-twe-ripple-init
                        onClick={() => navigate('/notificaciones')}
                        className="text-xs font-bold text-primary transition hover:text-primary-accent-300"
                      >
                        Ver todas
                      </button>
                    </div>
                  </div>
                  {notifList.length === 0 ? (
                    <div className="p-6 text-center text-sm font-medium text-neutral-400">
                      Sin notificaciones nuevas
                    </div>
                  ) : (
                    <div className="max-h-60 overflow-y-auto">
                      {notifList.slice(0, 5).map((n) => (
                        <div
                          key={n.id_notificacion}
                          className="group flex items-start justify-between gap-3 border-b border-neutral-50 px-4 py-3 transition-colors last:border-0 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-700/40"
                        >
                          <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium leading-snug text-neutral-700 dark:text-neutral-200">
                              {n.mensaje}
                            </p>
                            <p className="mt-1 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">
                              {formatNotifTime(n.fecha)}
                            </p>
                          </div>
                          <button
                            type="button"
                            data-twe-ripple-init
                            onClick={() => handleMarcarLeido(n.id_notificacion)}
                            title="Marcar como leída"
                            className="shrink-0 rounded-full p-1.5 text-primary opacity-0 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary-700 focus:outline-none group-hover:opacity-100 dark:hover:bg-primary-900/30"
                          >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>

            <div className="h-6 w-px bg-neutral-300"></div>

            <div className="relative" ref={userRef}>
              <button
                type="button"
                data-twe-ripple-init
                onClick={() => setUserDropdown(!userDropdown)}
                className="flex items-center gap-3 rounded-lg px-2 py-1.5 transition duration-150 ease-in-out hover:bg-neutral-100 focus:outline-none dark:hover:bg-neutral-800"
              >
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-800 text-sm font-bold text-white shadow-primary-3 dark:shadow-black/30">
                  {getInitials(displayName)}
                </div>
                <div className="hidden text-left lg:block">
                  <p className="text-sm font-bold leading-tight text-neutral-900 dark:text-neutral-100">
                    {displayName}
                  </p>
                  <p className="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                    {roleSublabel}
                  </p>
                </div>
                <svg
                  className={`hidden h-4 w-4 text-neutral-400 transition-transform duration-200 lg:block ${userDropdown ? 'rotate-180' : ''}`}
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {userDropdown && (
                <div className="absolute right-0 top-full z-50 mt-3 w-72 overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark dark:border dark:border-neutral-700">
                  <div className="flex items-center gap-3 border-b-2 border-neutral-100 bg-neutral-50/60 p-4 dark:border-neutral-700 dark:bg-neutral-800/40">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-800 text-sm font-bold text-white shadow-primary-3 dark:shadow-black/30">
                      {getInitials(displayName)}
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-bold text-neutral-900 dark:text-neutral-100">
                        {displayName}
                      </p>
                      <p className="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                        {rolePrefix}{roleSublabel}
                      </p>
                    </div>
                  </div>

                  <div className="p-2">
                    <Link
                      to="/cambiar-contrasena"
                      onClick={() => setUserDropdown(false)}
                      className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-700 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary dark:text-neutral-200 dark:hover:bg-primary-900/30"
                    >
                      <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                      </svg>
                      Cambiar contraseña
                    </Link>
                    <Link
                      to="/notificaciones"
                      onClick={() => setUserDropdown(false)}
                      className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-700 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary dark:text-neutral-200 dark:hover:bg-primary-900/30"
                    >
                      <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                      </svg>
                      Notificaciones
                      {notifCount > 0 && (
                        <span className={`${badge.danger} ml-auto`}>{notifCount > 9 ? '9+' : notifCount}</span>
                      )}
                    </Link>

                    <div className="my-2 border-t border-neutral-100 dark:border-neutral-700"></div>

                    <button
                      type="button"
                      onClick={() => { setUserDropdown(false); handleLogout() }}
                      className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-danger transition duration-150 ease-in-out hover:bg-danger-50 dark:hover:bg-danger-900/30"
                    >
                      <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      Cerrar sesión
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto bg-neutral-100 p-6">
          <Suspense
            fallback={
              <div className="flex items-center justify-center py-20">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
              </div>
            }
          >
            <Outlet />
          </Suspense>
        </main>
      </div>

      {sidebarOpen && (
        <div className="fixed inset-0 z-20 bg-black/50 lg:hidden" onClick={() => setSidebarOpen(false)} />
      )}
    </div>
  )
}
