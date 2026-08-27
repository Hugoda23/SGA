import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import { btn } from '../lib/twClasses'
import logo from '../assets/logo.jpg'

export default function Mantenimiento() {
  const [mensaje, setMensaje] = useState(sessionStorage.getItem('sga_mantenimiento_mensaje') || '')
  const [checking, setChecking] = useState(false)
  const navigate = useNavigate()

  useEffect(() => {
    api.get('/v1/sistema/estado').then((r) => {
      if (!r.data.mantenimiento_activo) {
        navigate('/login', { replace: true })
        return
      }
      if (r.data.mantenimiento_mensaje) setMensaje(r.data.mantenimiento_mensaje)
    }).catch(() => {})
  }, [navigate])

  const reintentar = async () => {
    setChecking(true)
    try {
      const r = await api.get('/v1/sistema/estado')
      if (!r.data.mantenimiento_activo) {
        sessionStorage.removeItem('sga_mantenimiento_mensaje')
        navigate('/login', { replace: true })
      } else {
        if (r.data.mantenimiento_mensaje) setMensaje(r.data.mantenimiento_mensaje)
      }
    } finally {
      setChecking(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-700 via-primary-800 to-neutral-900 p-4">
      <div className="w-full max-w-md rounded-xl border-none bg-white p-8 text-center shadow-5 dark:bg-surface-dark">
        <img src={logo} alt="Instituto Florencio Carrascoza" className="mx-auto mb-4 h-16 w-16 rounded-lg object-contain shadow-primary-3" />
        <h1 className="text-2xl font-bold text-neutral-800 dark:text-neutral-100">Instituto Florencio Carrascoza</h1>

        <div className="my-6 flex justify-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-warning-50 text-warning dark:bg-warning-900/20">
            <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.559-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </span>
        </div>

        <h2 className="mb-2 text-lg font-bold text-neutral-800 dark:text-neutral-100">Sistema en mantenimiento</h2>
        <p className="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
          {mensaje || 'Estamos actualizando el sistema. Volvé a intentarlo en unos minutos.'}
        </p>

        <button onClick={reintentar} disabled={checking} className={`${btn.primary} w-full disabled:cursor-not-allowed disabled:opacity-60`}>
          {checking ? 'Comprobando...' : 'Reintentar'}
        </button>
      </div>
    </div>
  )
}
