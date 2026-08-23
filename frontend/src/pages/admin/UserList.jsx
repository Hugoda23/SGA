import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function UserList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ username: '', password: '', rol: 'admin', estado: 'activo' })
  const [viewUser, setViewUser] = useState(null)
  const [editUser, setEditUser] = useState(null)
  const [search, setSearch] = useState('')
  const [filterRole, setFilterRole] = useState('')
  const [filterStatus, setFilterStatus] = useState('')
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmAction, setConfirmAction] = useState(null)
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [showPwd, setShowPwd] = useState({ new: false, confirm: false })

  const fetchData = async () => {
    try {
      const res = await api.get('/v1/usuarios/admin')
      setData(res.data)
    } catch (err) {
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { fetchData() }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await api.post('/v1/usuarios/admin', form)
      setShowForm(false)
      setForm({ username: '', password: '', rol: 'admin', estado: 'activo' })
      fetchData()
      setAlertMessage('Usuario creado exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al crear usuario')
    }
  }

  const handleUpdate = async (e) => {
    e.preventDefault()
    if (newPassword && newPassword.length < 6) {
      setAlertMessage('La contraseña debe tener al menos 6 caracteres.')
      return
    }
    if (newPassword && newPassword !== confirmPassword) {
      setAlertMessage('Las contraseñas no coinciden.')
      return
    }
    try {
      const payload = { username: editUser.username, estado: editUser.estado };
      if (editUser.hasProfile) {
        payload.nombre = editUser.nombre;
        payload.apellido = editUser.apellido;
        payload.correo = editUser.correo;
        payload.telefono = editUser.telefono;
      }
      await api.put(`/v1/usuarios/${editUser.id_usuario}`, payload)
      if (newPassword) {
        await api.put(`/v1/usuarios/${editUser.id_usuario}/password`, { password: newPassword })
      }
      setEditUser(null)
      fetchData()
      setAlertMessage('Usuario actualizado exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al actualizar')
    }
  }

  const handleDelete = (id) => {
    setConfirmAction({
      message: '¿Estás seguro de que deseas eliminar permanentemente este usuario?',
      onConfirm: async () => {
        try {
          await api.delete(`/v1/usuarios/${id}`);
          fetchData();
          setAlertMessage('Usuario eliminado correctamente.');
        } catch (err) {
          setAlertMessage(err.response?.data?.message || 'Error al eliminar');
        }
      }
    });
  }

  const filteredData = data.filter(u => {
    const matchesSearch = u.username.toLowerCase().includes(search.toLowerCase());
    const rolPrincipal = u.roles?.[0]?.nombre || 'Sin Rol';
    const matchesRole = filterRole ? (rolPrincipal.toLowerCase() === filterRole.toLowerCase()) : true;
    const matchesStatus = filterStatus ? u.estado === filterStatus : true;
    return matchesSearch && matchesRole && matchesStatus;
  })

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Gestión de Usuarios</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Administra accesos y datos del personal e institución.</p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className={btn.primary}
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Nuevo Usuario
        </button>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por nombre o matrícula..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
          <div className="flex flex-wrap gap-3">
            <select
              value={filterRole}
              onChange={(e) => setFilterRole(e.target.value)}
              className={`${input.base} w-auto`}
            >
              <option value="">Todos los roles</option>
              <option value="admin">Admin</option>
              <option value="director">Director</option>
              <option value="secretaria">Secretaria</option>
              <option value="catedratico">Catedrático</option>
              <option value="alumno">Alumno</option>
            </select>
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className={`${input.base} w-auto`}
            >
              <option value="">Todos los estados</option>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>MATRÍCULA/ID</th>
                <th className={tbl.th}>NOMBRE COMPLETO</th>
                <th className={tbl.th}>ROL PRINCIPAL</th>
                <th className={tbl.th}>ESTADO</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Cargando usuarios...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredData.length === 0 ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No se encontraron resultados</span>
                      <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo usuario.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                filteredData.map((user) => {
                  const rolPrincipal = user.roles?.[0]?.nombre || 'Sin Rol';
                  return (
                    <tr key={user.id_usuario} className={tbl.row}>
                      <td className={tbl.td}>
                        <span className="font-medium text-neutral-700">{user.username}</span>
                      </td>
                      <td className={`${tbl.td} font-medium`}>
                        {user.alumno ? `${user.alumno.nombre} ${user.alumno.apellido}` : user.catedratico ? `${user.catedratico.nombre} ${user.catedratico.apellido}` : 'Administrador / Personal'}
                      </td>
                      <td className={tbl.td}>
                        <span className={`${badge.primary} capitalize`}>
                          {rolPrincipal}
                        </span>
                      </td>
                      <td className={tbl.td}>
                        <span className={user.estado === 'activo' ? badge.success : badge.danger}>
                          {user.estado === 'activo' ? 'Activo' : 'Inactivo'}
                        </span>
                      </td>
                      <td className="whitespace-nowrap px-4 py-3 text-right">
                        <div className="flex justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => setViewUser(user)}
                            className="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10"
                          >
                            Ver
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              const profile = user.alumno || user.catedratico;
                              setEditUser({ 
                                id_usuario: user.id_usuario, 
                                username: user.username, 
                                estado: user.estado,
                                hasProfile: !!profile,
                                nombre: profile?.nombre || '',
                                apellido: profile?.apellido || '',
                                correo: profile?.correo || '',
                                telefono: profile?.telefono || '',
                              });
                              setNewPassword('')
                              setConfirmPassword('')
                              setShowPwd({ new: false, confirm: false })
                            }}
                            className="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10"
                          >
                            Editar
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDelete(user.id_usuario)}
                            className="rounded-lg bg-danger-50 px-3 py-1.5 text-xs font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
                          >
                            Eliminar
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      <Modal
        open={showForm}
        onClose={() => setShowForm(false)}
        title="Nuevo Usuario"
        size="md"
        footer={
          <>
            <button type="submit" form="form-user-create" className={btn.primary}>Crear</button>
            <button type="button" onClick={() => setShowForm(false)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-user-create" onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className={input.label}>Usuario *</label>
            <input name="username" value={form.username} onChange={handleChange} required className={input.base} />
          </div>
          <div>
            <label className={input.label}>Contraseña *</label>
            <input name="password" type="password" value={form.password} onChange={handleChange} required minLength={6} className={input.base} />
          </div>
          <div>
            <label className={input.label}>Rol *</label>
            <select name="rol" value={form.rol} onChange={handleChange} className={input.base}>
              <option value="admin">Admin</option>
              <option value="director">Director</option>
              <option value="secretaria">Secretaria</option>
            </select>
          </div>
        </form>
      </Modal>

      {viewUser && (
      <Modal
        open
        onClose={() => setViewUser(null)}
        title="Detalles del Usuario"
        size="md"
        footer={
          <button type="button" onClick={() => setViewUser(null)} className={btn.neutral}>Cerrar</button>
        }
      >
        <div className="space-y-3 text-sm">
          <p><span className="font-semibold text-neutral-700 dark:text-neutral-300">Username:</span> {viewUser.username}</p>
          <p><span className="font-semibold text-neutral-700 dark:text-neutral-300">Estado:</span> <span className="capitalize">{viewUser.estado}</span></p>
          <p><span className="font-semibold text-neutral-700 dark:text-neutral-300">Rol Principal:</span> <span className="capitalize">{viewUser.roles?.[0]?.nombre || 'Sin rol'}</span></p>
          <p><span className="font-semibold text-neutral-700 dark:text-neutral-300">Nombre Completo:</span> {viewUser.alumno ? `${viewUser.alumno.nombre} ${viewUser.alumno.apellido}` : viewUser.catedratico ? `${viewUser.catedratico.nombre} ${viewUser.catedratico.apellido}` : 'Administrador / Personal'}</p>
        </div>
      </Modal>
      )}

      {editUser && (
      <Modal
        open
        onClose={() => setEditUser(null)}
        title="Editar Usuario"
        size="md"
        scrollable
        footer={
          <>
            <button type="submit" form="form-user-edit" className={btn.primary}>Guardar Cambios</button>
            <button type="button" onClick={() => setEditUser(null)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-user-edit" onSubmit={handleUpdate} className="space-y-4">
          <div>
            <label className={input.label}>Usuario *</label>
            <input value={editUser.username} onChange={(e) => setEditUser({...editUser, username: e.target.value})} required className={input.base} />
          </div>
          <div>
            <label className={input.label}>Estado</label>
            <select value={editUser.estado} onChange={(e) => setEditUser({...editUser, estado: e.target.value})} className={input.base}>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
          {editUser.hasProfile && (
            <>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={input.label}>Nombre</label>
                  <input value={editUser.nombre} onChange={(e) => setEditUser({...editUser, nombre: e.target.value})} required className={input.base} />
                </div>
                <div>
                  <label className={input.label}>Apellido</label>
                  <input value={editUser.apellido} onChange={(e) => setEditUser({...editUser, apellido: e.target.value})} required className={input.base} />
                </div>
              </div>
              <div>
                <label className={input.label}>Correo Electrónico</label>
                <input type="email" value={editUser.correo} onChange={(e) => setEditUser({...editUser, correo: e.target.value})} required className={input.base} />
              </div>
              <div>
                <label className={input.label}>Teléfono</label>
                <input value={editUser.telefono} onChange={(e) => setEditUser({...editUser, telefono: e.target.value})} required className={input.base} />
              </div>
            </>
          )}
          <div className="border-t border-neutral-100 pt-4 dark:border-neutral-700">
            <p className="mb-3 text-xs text-neutral-500">Cambiar contraseña (opcional — dejar vacío para mantener la actual)</p>
            <div>
              <label className={input.label}>Nueva Contraseña</label>
              <div className="relative">
                <input type={showPwd.new ? 'text' : 'password'} value={newPassword} onChange={(e) => setNewPassword(e.target.value)} minLength={6} className={`${input.base} pr-10`} />
                <button type="button" onClick={() => setShowPwd({...showPwd, new: !showPwd.new})} className="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">
                  {showPwd.new ? (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                  ) : (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  )}
                </button>
              </div>
            </div>
            <div className="mt-3">
              <label className={input.label}>Confirmar Nueva Contraseña</label>
              <div className="relative">
                <input type={showPwd.confirm ? 'text' : 'password'} value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} minLength={6} className={`${input.base} pr-10`} />
                <button type="button" onClick={() => setShowPwd({...showPwd, confirm: !showPwd.confirm})} className="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">
                  {showPwd.confirm ? (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                  ) : (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  )}
                </button>
              </div>
            </div>
          </div>
        </form>
      </Modal>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Mensaje del Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>

      {confirmAction && (
      <Modal
        open
        onClose={() => setConfirmAction(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmAction(null)} className={btn.ghost}>Cancelar</button>
            <button
              type="button"
              onClick={() => { confirmAction.onConfirm(); setConfirmAction(null) }}
              className={btn.danger}
            >
              Sí, Confirmar
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{confirmAction.message}</p>
      </Modal>
      )}
    </div>
  )
}
