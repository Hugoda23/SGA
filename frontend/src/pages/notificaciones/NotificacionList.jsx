import { useState, useEffect, useCallback } from 'react'
import api from '../../api/axios'
import { btn, input } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function NotificacionList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [filtro, setFiltro] = useState('todas')
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchData = useCallback(() => {
    setLoading(true)
    const url = filtro === 'no-leidas'
      ? '/v1/notificaciones/no-leidas'
      : '/v1/notificaciones/mias'
    api.get(url).then((r) => setData(r.data)).catch(console.error).finally(() => setLoading(false))
  }, [filtro])

  useEffect(() => { fetchData() }, [fetchData])

  const handleMarcarLeido = async (id) => {
    try {
      await api.patch(`/v1/notificaciones/${id}/leido`)
      fetchData()
    } catch (err) {
      console.error(err)
    }
  }

  const handleMarcarTodasLeidas = async () => {
    try {
      await api.post('/v1/notificaciones/marcar-todas-leidas')
      fetchData()
      setAlertMessage('Todas las notificaciones marcadas como leídas.')
    } catch (err) {
      console.error(err)
    }
  }

  const noLeidasCount = data.filter((n) => !n.leido).length

  return (
    <div className="max-w-4xl mx-auto pb-12">
      <div className="flex justify-between items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">Notificaciones</h1>
          <p className="text-neutral-500 font-medium mt-1">
            {noLeidasCount > 0 ? `${noLeidasCount} sin leer` : 'Todas leídas'}
          </p>
        </div>
        <div className="flex gap-3">
          <select
            value={filtro}
            onChange={(e) => setFiltro(e.target.value)}
            className={input.base}
          >
            <option value="todas">Todas</option>
            <option value="no-leidas">No leídas</option>
          </select>
          {noLeidasCount > 0 && (
            <button onClick={handleMarcarTodasLeidas} className={btn.outline}>
              Marcar todas leídas
            </button>
          )}
        </div>
      </div>

      {loading && (
        <div className="flex flex-col items-center py-16 text-neutral-500">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary-100 border-t-primary mb-4"></div>
          <span className="font-semibold">Cargando...</span>
        </div>
      )}

      {!loading && data.length === 0 && (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          <p className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay notificaciones</p>
        </div>
      )}

      {!loading && data.length > 0 && (
        <div className="space-y-3">
          {data.map((notif) => (
            <div
              key={notif.id_notificacion}
              className={`rounded-xl bg-white p-5 border shadow-4 transition-all hover:shadow-md flex justify-between items-start gap-4 dark:bg-surface-dark ${!notif.leido ? 'border-l-4 border-l-primary bg-primary-50/30' : 'border-neutral-100'}`}
            >
              <div className="flex-1 min-w-0">
                <p className={`text-sm ${notif.leido ? 'text-neutral-600 dark:text-neutral-300' : 'text-neutral-900 dark:text-neutral-100 font-semibold'}`}>{notif.mensaje}</p>
                <p className="text-xs text-neutral-400 mt-2">{new Date(notif.fecha).toLocaleString('es-GT')}</p>
              </div>
              {!notif.leido && (
                <button
                  onClick={() => handleMarcarLeido(notif.id_notificacion)}
                  className="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-900/30 dark:text-primary-300 shrink-0"
                >
                  Marcar leído
                </button>
              )}
            </div>
          ))}
        </div>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Aviso"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 mb-2">{alertMessage}</p>
      </Modal>
    </div>
  )
}
