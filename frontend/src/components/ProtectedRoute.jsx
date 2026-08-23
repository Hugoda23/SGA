import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function ProtectedRoute({ children, roles, permisos }) {
  const { user, loading, hasRole, hasPermiso } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-neutral-100">
        <div className="h-14 w-14 animate-spin rounded-full border-4 border-primary-100 border-t-primary" />
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />

  if (roles && !hasRole(...roles)) {
    return <Navigate to="/" replace />
  }

  if (permisos && !hasPermiso(...permisos)) {
    return <Navigate to="/" replace />
  }

  return children
}
