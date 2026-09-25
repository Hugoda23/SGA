import { createContext, useContext, useState, useEffect, useCallback } from 'react'
import api from '../api/axios'
import { subscribeToPush, unsubscribeFromPush } from '../lib/push'

const AuthContext = createContext(null)

const USER_KEY = 'sga_user'

// El storage puede quedar con basura (la cadena "undefined" si alguna vez se
// guardó una respuesta sin usuario, o HTML servido por el proxy). Si no parsea
// se descarta en lugar de romper el arranque de la app.
function leerUsuarioGuardado() {
  const stored = localStorage.getItem(USER_KEY)
  if (!stored || stored === 'undefined' || stored === 'null') {
    localStorage.removeItem(USER_KEY)
    return null
  }
  try {
    const usuario = JSON.parse(stored)
    return usuario && typeof usuario === 'object' ? usuario : null
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

function guardarUsuario(usuario) {
  if (!usuario || typeof usuario !== 'object') {
    localStorage.removeItem(USER_KEY)
    return
  }
  localStorage.setItem(USER_KEY, JSON.stringify(usuario))
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(leerUsuarioGuardado)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('sga_token')
    if (token) {
      api.get('/v1/auth/me')
        .then((res) => {
          if (!res.data || typeof res.data !== 'object') {
            throw new Error('Respuesta inválida de /v1/auth/me')
          }
          setUser(res.data)
          guardarUsuario(res.data)
          // Silencioso: solo re-suscribe si el permiso ya fue concedido antes.
          if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            subscribeToPush().catch(() => {})
          }
        })
        .catch(() => {
          localStorage.removeItem('sga_token')
          localStorage.removeItem(USER_KEY)
          setUser(null)
        })
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [])

  const login = useCallback(async (codigo, password, tipo) => {
    const res = await api.post('/v1/auth/login', { codigo, password, tipo })
    const usuario = res.data?.usuario
    if (!res.data?.token || !usuario || typeof usuario !== 'object') {
      throw new Error('La respuesta del servidor no incluyó la sesión. Intentá de nuevo.')
    }
    localStorage.setItem('sga_token', res.data.token)
    guardarUsuario(usuario)
    setUser(usuario)
    // Pide permiso de notificaciones justo después del login (gesto del usuario).
    subscribeToPush().catch(() => {})
    return res.data
  }, [])

  const changePassword = useCallback(async (currentPassword, newPassword, confirmation) => {
    const res = await api.post('/v1/auth/change-password', {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: confirmation,
    })
    const updated = { ...user, password_change_required: false }
    setUser(updated)
    guardarUsuario(updated)
    return res.data
  }, [user])

  const logout = useCallback(async () => {
    await unsubscribeFromPush().catch(() => {})
    try {
      await api.post('/v1/auth/logout')
    } catch {
      // ignore
    }
    localStorage.removeItem('sga_token')
    localStorage.removeItem(USER_KEY)
    setUser(null)
  }, [])

  const hasRole = useCallback((...roles) => {
    if (!user?.roles) return false
    return user.roles.some((r) => roles.includes(r.nombre))
  }, [user])

  const hasPermiso = useCallback((...permisos) => {
    const userPermisos = user?.permisos || []
    return permisos.some((p) => userPermisos.includes(p))
  }, [user])

  return (
    <AuthContext.Provider value={{ user, login, logout, changePassword, loading, hasRole, hasPermiso }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
