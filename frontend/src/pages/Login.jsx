import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { btn, input } from '../lib/twClasses'
import logo from '../assets/logo.jpg'

const tipos = [
  { value: 'administrador', label: 'Administrador', desc: 'Admin / Director / Secretaría' },
  { value: 'docente', label: 'Docente', desc: 'Catedrático' },
  { value: 'estudiante', label: 'Estudiante', desc: 'Alumno' },
]

export default function Login() {
  const [tipo, setTipo] = useState('')
  const [codigo, setCodigo] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!tipo) { setError('Seleccione un tipo de usuario'); return }
    setError('')
    setLoading(true)
    try {
      const res = await login(codigo, password, tipo)
      if (res.password_change_required) {
        navigate('/cambiar-contrasena')
      } else {
        navigate('/')
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.tipo?.[0] || err.response?.data?.message || 'Credenciales incorrectas'
      setError(msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-700 via-primary-800 to-neutral-900 p-4">
      <div className="w-full max-w-md rounded-xl border-none bg-white p-8 shadow-5 dark:bg-surface-dark">
        <div className="mb-8 text-center">
          <img src={logo} alt="Instituto Florencio Carrascoza" className="mx-auto mb-4 h-16 w-16 rounded-lg object-contain shadow-primary-3" />
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">SGA</h1>
          <p className="mt-1 text-neutral-500 dark:text-neutral-400">Sistema de Gestión Académica</p>
        </div>

        <div className="mb-6 grid grid-cols-3 gap-2">
          {tipos.map((t) => (
            <button
              key={t.value}
              type="button"
              data-twe-ripple-init
              onClick={() => { setTipo(t.value); setError('') }}
              className={`rounded-lg px-3 py-3 text-sm font-bold transition-all ${
                tipo === t.value
                  ? 'bg-primary text-white shadow-primary-3'
                  : 'border border-neutral-200 bg-neutral-50 text-neutral-600 hover:border-primary-400 hover:bg-white'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          {error && (
            <div className="rounded-lg bg-danger-50 px-4 py-3 text-sm font-semibold text-danger dark:bg-danger-900/30 dark:text-danger-300">
              {error}
            </div>
          )}
          <div>
            <label className={input.label}>Código</label>
            <input
              type="text"
              value={codigo}
              onChange={(e) => setCodigo(e.target.value)}
              required
              placeholder="Ingrese su código"
              className={input.base}
            />
          </div>
          <div>
            <label className={input.label}>Contraseña</label>
            <div className="relative">
              <input
                type={showPassword ? 'text' : 'password'}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="Ingrese su contraseña"
                className={`${input.base} pl-14`}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                className="absolute left-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none dark:hover:bg-neutral-700 dark:hover:text-neutral-200"
              >
                {showPassword ? (
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                ) : (
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
          <button
            type="submit"
            disabled={loading}
            className={`${btn.primary} w-full disabled:cursor-not-allowed disabled:opacity-60`}
          >
            {loading ? 'Ingresando...' : 'Ingresar'}
          </button>
        </form>
      </div>
    </div>
  )
}
