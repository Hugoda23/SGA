import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { btn, input } from '../lib/twClasses'

export default function ChangePassword() {
  const [form, setForm] = useState({ current_password: '', new_password: '', confirm_password: '' })
  const [show, setShow] = useState({ current: false, new: false, confirm: false })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const { changePassword } = useAuth()
  const navigate = useNavigate()

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    if (form.new_password !== form.confirm_password) {
      setError('Las contraseñas nuevas no coinciden.')
      return
    }
    if (form.new_password.length < 8) {
      setError('La contraseña debe tener al menos 8 caracteres.')
      return
    }
    if (!/[A-Za-z]/.test(form.new_password) || !/[0-9]/.test(form.new_password)) {
      setError('La contraseña debe contener al menos una letra y un número.')
      return
    }
    setLoading(true)
    try {
      await changePassword(form.current_password, form.new_password, form.confirm_password)
      navigate('/')
    } catch (err) {
      const msg = err.response?.data?.errors?.current_password?.[0]
        || err.response?.data?.errors?.new_password?.[0]
        || err.response?.data?.message
        || 'Error al cambiar la contraseña'
      setError(msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-700 via-primary-800 to-neutral-900 p-4">
      <div className="w-full max-w-md rounded-xl border-none bg-white p-8 shadow-5 dark:bg-surface-dark">
        <div className="mb-8 text-center">
          <h1 className="text-3xl font-bold tracking-tight text-neutral-800 dark:text-neutral-100">Cambiar Contraseña</h1>
          <p className="mt-1 text-neutral-500 dark:text-neutral-400">Debe cambiar su contraseña antes de continuar</p>
        </div>
        <form onSubmit={handleSubmit} className="space-y-5">
          {error && (
            <div className="rounded-lg bg-danger-50 px-4 py-3 text-sm font-semibold text-danger dark:bg-danger-900/30 dark:text-danger-300">
              {error}
            </div>
          )}
          <PasswordField label="Contraseña Actual" name="current_password" value={form.current_password} show={show.current} onToggle={() => setShow({ ...show, current: !show.current })} onChange={handleChange} />
          <PasswordField label="Nueva Contraseña" name="new_password" value={form.new_password} show={show.new} onToggle={() => setShow({ ...show, new: !show.new })} onChange={handleChange} minLength={8} />
          <PasswordField label="Confirmar Nueva Contraseña" name="confirm_password" value={form.confirm_password} show={show.confirm} onToggle={() => setShow({ ...show, confirm: !show.confirm })} onChange={handleChange} />
          <button type="submit" disabled={loading}
            className={`${btn.primary} w-full disabled:cursor-not-allowed disabled:opacity-60`}>
            {loading ? 'Cambiando...' : 'Cambiar Contraseña'}
          </button>
        </form>
      </div>
    </div>
  )
}

function PasswordField({ label, name, value, show, onToggle, onChange, minLength }) {
  return (
    <div>
      <label className={input.label}>{label}</label>
      <div className="relative">
        <input type={show ? 'text' : 'password'} name={name} value={value} onChange={onChange} autoComplete={name === 'current_password' ? 'off' : 'new-password'} required minLength={minLength}
          className={`${input.base} pr-14`} />
        <button type="button" onClick={onToggle} aria-label={show ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          className="absolute right-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none dark:hover:bg-neutral-700 dark:hover:text-neutral-200">
          {show ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
          ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
          )}
        </button>
      </div>
    </div>
  )
}
