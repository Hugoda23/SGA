import { useState, useEffect, useRef, useCallback } from 'react'
import api from '../../api/axios'
import { useAuth } from '../../context/AuthContext'
import { btn, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const FORMATOS_ACEPTADOS = '.pdf,.zip,.rar,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.odt,.txt,.jpg,.jpeg,.png,.gif'

export default function MisTareas() {
  const { user } = useAuth()
  const [tareas, setTareas] = useState([])
  const [loading, setLoading] = useState(true)
  const [modalSubida, setModalSubida] = useState(null)
  const [presentando, setPresentando] = useState(false)
  const [successModal, setSuccessModal] = useState(null)
  const [alertMessage, setAlertMessage] = useState(null)
  const fileInputRefs = useRef({})
  const [modalLinkTarea, setModalLinkTarea] = useState(null)
  const [linkValue, setLinkValue] = useState('')
  const [enviandoLink, setEnviandoLink] = useState(false)

  const isAlumno = user?.roles?.some((r) => r.nombre === 'alumno')

  const fetchTareas = useCallback(() => {
    if (!isAlumno) { setLoading(false); return }
    setLoading(true)
    api.get('/v1/mis-tareas').then((r) => setTareas(r.data)).catch(console.error).finally(() => setLoading(false))
  }, [isAlumno])

  useEffect(() => { fetchTareas() }, [fetchTareas])

  const handleSubirArchivo = async (id_tarea, file) => {
    if (!file) return
    const formData = new FormData()
    formData.append('id_tarea', id_tarea)
    formData.append('archivo', file)
    setModalSubida({ id_tarea, fase: 'subiendo', progreso: 0, nombre: file.name, id_entrega: null, tipo: 'archivo' })
    try {
      const res = await api.post('/v1/entregas-tarea/subir-archivo', formData, {
        headers: { 'Content-Type': null },
        onUploadProgress: (e) => {
          if (e.total) setModalSubida((prev) => prev ? { ...prev, progreso: Math.round((e.loaded * 100) / e.total) } : prev)
        },
      })
      setModalSubida((prev) => prev ? { ...prev, fase: 'subido', id_entrega: res.data.id_entrega } : prev)
      if (fileInputRefs.current[id_tarea]) fileInputRefs.current[id_tarea].value = ''
    } catch (err) {
      setModalSubida(null)
      if (fileInputRefs.current[id_tarea]) fileInputRefs.current[id_tarea].value = ''
      const data = err.response?.data
      const msg = data?.message || 'Error al subir archivo'
      const errores = data?.errors
      if (errores?.archivo) {
        setAlertMessage(errores.archivo.join(', '))
      } else if (errores) {
        const primerError = Object.values(errores)[0]
        setAlertMessage(Array.isArray(primerError) ? primerError[0] : msg)
      } else {
        setAlertMessage(msg)
      }
    }
  }

  const presentarEntrega = async (id_entrega, nombre, tipo = 'archivo') => {
    if (!id_entrega || presentando) return
    setPresentando(true)
    try {
      await api.post(`/v1/entregas-tarea/presentar/${id_entrega}`)
      setModalSubida(null)
      setSuccessModal({ nombre, tipo })
      fetchTareas()
    } catch (err) {
      setModalSubida(null)
      setAlertMessage(err.response?.data?.error || err.response?.data?.message || 'Error al presentar la tarea')
    } finally {
      setPresentando(false)
    }
  }

  const abrirModalLink = (tarea) => {
    setLinkValue(tarea.mi_entrega?.link || '')
    setModalLinkTarea(tarea.id_tarea)
  }

  const handleEnviarLink = async () => {
    if (!modalLinkTarea) return
    const link = linkValue.trim()
    if (!link) { setAlertMessage('Ingresa el enlace de tu entrega'); return }
    setEnviandoLink(true)
    try {
      const res = await api.post('/v1/entregas-tarea/subir-archivo', {
        id_tarea: modalLinkTarea,
        link,
      })
      setModalLinkTarea(null)
      setLinkValue('')
      setModalSubida({ id_tarea: modalLinkTarea, fase: 'subido', progreso: 100, nombre: link, id_entrega: res.data.id_entrega, tipo: 'enlace' })
    } catch (err) {
      setAlertMessage(err.response?.data?.error || err.response?.data?.message || 'Error al enviar el enlace')
    } finally {
      setEnviandoLink(false)
    }
  }

  if (!isAlumno) {
    return (
      <div className="max-w-4xl mx-auto pb-12">
        <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-6">Mis Tareas</h1>
        <div className="rounded-xl border border-warning-200 bg-warning-50 p-6 text-sm font-medium text-warning dark:bg-warning-900/30 dark:text-warning-300">
          Esta sección es solo para alumnos.
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-4xl mx-auto pb-12">
      <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-2">Mis Tareas</h1>
      <p className="text-neutral-500 font-medium mb-8">Tareas pendientes y entregadas.</p>

      {loading && (
        <div className="flex flex-col items-center py-16 text-neutral-500">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary-100 border-t-primary mb-4"></div>
          <span className="font-semibold">Cargando...</span>
        </div>
      )}

      {!loading && tareas.length === 0 && (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
          <p className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No tienes tareas asignadas</p>
        </div>
      )}

      {!loading && tareas.map((t) => {
        const fechaLimite = t.fecha_entrega ? new Date(t.fecha_entrega) : null
        const vencida = fechaLimite && fechaLimite < new Date()
        const entrega = t.mi_entrega
        const presentada = entrega?.estado === 'entregada'
        const esBorrador = entrega?.estado === 'borrador'
        const presentandoEsta = presentando && modalSubida?.id_tarea === t.id_tarea

        const renderReemplazar = () => (
          <div className="mt-2 flex flex-wrap justify-end gap-2">
            <label className="cursor-pointer inline-block bg-primary-100 text-primary px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-primary-200 transition-all dark:bg-primary-900/30 dark:text-primary-300">
              Reemplazar archivo
              <input ref={(el) => fileInputRefs.current[t.id_tarea] = el} type="file" accept={FORMATOS_ACEPTADOS} className="hidden" onChange={(e) => { if (e.target.files[0]) handleSubirArchivo(t.id_tarea, e.target.files[0]) }} />
            </label>
            {t.permitir_link && (
              <button onClick={() => abrirModalLink(t)} className="cursor-pointer inline-block bg-primary-100 text-primary px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-primary-200 transition-all dark:bg-primary-900/30 dark:text-primary-300">
                Reemplazar enlace
              </button>
            )}
          </div>
        )

        const renderDetalleEntrega = (etiqueta, cls) => (
          <div className="text-right">
            <span className={cls}>{etiqueta}</span>
            {entrega.nombre_original && (
              <p className="text-[10px] text-neutral-400 mt-1 truncate max-w-[150px]" title={entrega.nombre_original}>{entrega.nombre_original}</p>
            )}
            {entrega.link && (
              <a href={entrega.link} target="_blank" rel="noreferrer" className="text-[10px] font-bold text-primary underline mt-1 block truncate max-w-[150px] hover:text-primary-accent-300" title={entrega.link}>
                {entrega.link}
              </a>
            )}
            {entrega.calificacion !== null && (
              <div className="mt-2 text-lg font-extrabold text-primary">{entrega.calificacion} pts</div>
            )}
          </div>
        )

        return (
          <div key={t.id_tarea} className={`rounded-xl bg-white p-6 mb-4 border shadow-4 dark:bg-surface-dark ${vencida && !presentada && !esBorrador ? 'border-danger-200 bg-danger-50/30' : vencida && presentada ? 'border-warning-200' : 'border-neutral-100'}`}>
            <div className="flex justify-between items-start gap-4">
              <div className="flex-1 min-w-0">
                <div className="text-xs font-bold text-primary mb-1">{t.curso}</div>
                <h3 className="font-bold text-neutral-800 text-lg dark:text-neutral-100">{t.titulo}</h3>
                {t.descripcion && <p className="text-sm text-neutral-500 mt-1 whitespace-pre-line">{t.descripcion}</p>}
                {fechaLimite && (
                  <p className={`text-xs mt-2 font-medium ${vencida ? 'text-danger' : 'text-neutral-400'}`}>
                    Límite: {fechaLimite.toLocaleString('es-GT')}
                    {vencida && !presentada && <span className="font-bold ml-2">— TIEMPO AGOTADO —</span>}
                  </p>
                )}
              </div>
              <div className="shrink-0 text-right min-w-[140px]">
                {presentada ? (
                  <div>
                    {renderDetalleEntrega('Entregado', badge.success)}
                    {!vencida && renderReemplazar()}
                  </div>
                ) : esBorrador ? (
                  <div>
                    {renderDetalleEntrega('Borrador', badge.warning)}
                    <div className="mt-2">
                      <button
                        onClick={() => presentarEntrega(entrega.id_entrega, entrega.nombre_original || entrega.link, entrega.link ? 'enlace' : 'archivo')}
                        disabled={presentando}
                        className={`${btn.primary} disabled:opacity-50`}
                      >
                        {presentandoEsta ? 'Presentando...' : 'Presentar Tarea'}
                      </button>
                    </div>
                    <p className="text-[9px] text-neutral-400 mt-1.5">Tu docente verá la tarea al presentarla.</p>
                    {!vencida && renderReemplazar()}
                  </div>
                ) : vencida ? (
                  <div>
                    <span className={badge.danger}>BLOQUEADA</span>
                    <p className="text-[10px] text-danger mt-1">Su tiempo ya pasó</p>
                  </div>
                ) : (
                  <div className="flex flex-col items-end gap-1.5">
                    <label className={`${btn.primary} cursor-pointer`}>
                      Subir archivo
                      <input ref={(el) => fileInputRefs.current[t.id_tarea] = el} type="file" accept={FORMATOS_ACEPTADOS} className="hidden" onChange={(e) => { if (e.target.files[0]) handleSubirArchivo(t.id_tarea, e.target.files[0]) }} />
                    </label>
                    {t.permitir_link && (
                      <button onClick={() => abrirModalLink(t)} className={btn.secondary}>
                        Enviar enlace
                      </button>
                    )}
                    <p className="text-[9px] text-neutral-400">PDF, ZIP, RAR, Word, etc.</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )
      })}

      {successModal && (
      <Modal
        open
        onClose={() => setSuccessModal(null)}
        title="¡Tarea presentada!"
        size="sm"
        footer={
          <button type="button" onClick={() => setSuccessModal(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <div className="text-center">
          <div className="w-16 h-16 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
          </div>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-1">
            {successModal.tipo === 'enlace' ? 'Se ha presentado el enlace de tu entrega:' : 'Se ha presentado tu documento:'}
          </p>
          <p className="text-sm font-bold text-primary mb-2 break-all">{successModal.nombre}</p>
          <p className="text-xs text-neutral-400">Tu docente ya puede visualizar la tarea.</p>
        </div>
      </Modal>
      )}

      <Modal
        open={!!modalLinkTarea}
        onClose={() => setModalLinkTarea(null)}
        title="Entregar tarea por enlace"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setModalLinkTarea(null)} className={btn.neutral}>Cancelar</button>
            <button type="button" onClick={handleEnviarLink} disabled={enviandoLink} className={`${btn.primary} disabled:opacity-50`}>
              {enviandoLink ? 'Enviando...' : 'Enviar enlace'}
            </button>
          </>
        }
      >
        <div>
          <label className="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-300">Enlace de tu entrega</label>
          <input
            type="url"
            value={linkValue}
            onChange={(e) => setLinkValue(e.target.value)}
            placeholder="https://drive.google.com/..."
            className="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm text-neutral-700 shadow-2 transition duration-150 ease-in-out placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
          />
          <p className="mt-1.5 text-xs text-neutral-400 dark:text-neutral-500">Ej. un enlace a Google Drive, Dropbox, GitHub, etc.</p>
        </div>
      </Modal>

      <Modal
        open={!!modalSubida}
        onClose={() => { if (modalSubida?.fase === 'subido') setModalSubida(null) }}
        title={modalSubida?.fase === 'subiendo' ? 'Subiendo documento' : 'Documento subido'}
        size="sm"
        footer={
          modalSubida?.fase === 'subido' ? (
            <>
              <button type="button" onClick={() => setModalSubida(null)} className={btn.neutral}>Cerrar</button>
              <button
                type="button"
                onClick={() => presentarEntrega(modalSubida?.id_entrega, modalSubida?.nombre, modalSubida?.tipo)}
                disabled={presentando}
                className={`${btn.primary} disabled:opacity-50`}
              >
                {presentando ? 'Presentando...' : 'Presentar Tarea'}
              </button>
            </>
          ) : null
        }
      >
        {modalSubida ? (
          modalSubida.fase === 'subiendo' ? (
            <div className="text-center">
              <div className="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
              <p className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Subiendo documento...</p>
              <div className="mx-auto mt-4 w-full max-w-xs bg-neutral-100 dark:bg-neutral-700 rounded-full h-2.5 overflow-hidden">
                <div className="bg-primary h-full rounded-full transition-all duration-300" style={{ width: `${modalSubida.progreso}%` }}></div>
              </div>
              <span className="mt-1 block text-[10px] font-bold text-primary">{modalSubida.progreso}%</span>
              <p className="mt-2 text-xs text-neutral-400 break-all">{modalSubida.nombre}</p>
            </div>
          ) : (
            <div className="text-center">
              <div className="w-16 h-16 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
              </div>
              <p className="text-base font-bold text-neutral-800 dark:text-neutral-100">Documento subido</p>
              <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400 break-all">{modalSubida.nombre}</p>
              <p className="mt-3 text-xs text-neutral-400">
                Pulsa <span className="font-bold text-primary">Presentar Tarea</span> para que tu docente pueda visualizarla.
              </p>
            </div>
          )
        ) : null}
      </Modal>

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
        <div className="text-center">
          <div className="w-12 h-12 bg-danger-100 dark:bg-danger-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg className="w-6 h-6 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <p className="text-sm text-neutral-600 dark:text-neutral-300 mb-2">{alertMessage}</p>
        </div>
      </Modal>
    </div>
  )
}
