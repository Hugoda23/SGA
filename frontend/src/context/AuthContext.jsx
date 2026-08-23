import { createContext, useContext, useState, useEffect, useCallback } from 'react'
import api from '../api/axios'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('sga_user')
    return stored ? JSON.parse(stored) : null
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('sga_token')
    if (token) {
      api.get('/v1/auth/me')
        .then((res) => {
          setUser(res.data)
          localStorage.setItem('sga_user', JSON.stringify(res.data))
        })
        .catch(() => {
          localStorage.removeItem('sga_token')
          localStorage.removeItem('sga_user')
          setUser(null)
        })
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [])

  const login = useCallback(async (codigo, password, tipo) => {
    const res = await api.post('/v1/auth/login', { codigo, password, tipo })
    localStorage.setItem('sga_token', res.data.token)
    localStorage.setItem('sga_user', JSON.stringify(res.data.usuario))
    setUser(res.data.usuario)
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
    localStorage.setItem('sga_user', JSON.stringify(updated))
    return res.data
  }, [user])

  const logout = useCallback(async () => {
    try {
      await api.post('/v1/auth/logout')
    } catch {
      // ignore
    }
    localStorage.removeItem('sga_token')
    localStorage.removeItem('sga_user')
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
