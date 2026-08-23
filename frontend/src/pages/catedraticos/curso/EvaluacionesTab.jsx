import { useState } from 'react'
import api from '../../../api/axios'
import { btn, input, badge } from '../../../lib/twClasses'
import Modal from '../../../components/Modal'

export default function EvaluacionesTab({ idAsignacion, zonas, evaluaciones, totalPuntosZonas, reload, setAlert }) {
  const [nuevaZona, setNuevaZona] = useState({ nombre: '', puntos: '' })
  const [guardandoZona, setGuardandoZona] = useState(false)

  const [zonaEditando, setZonaEditando] = useState(null)
  const [zonaForm, setZonaForm] = useState({ nombre: '', puntos: '' })

  const [modalActividad, setModalActividad] = useState(null)
  const [actividadForm, setActividadForm] = useState({ id_zona: '', nombre: '', porcentaje: '' })
  const [guardandoActividad, setGuardandoActividad] = useState(false)

  const sinZona = evaluaciones.filter((ev) => !ev.id_zona)
  const total = parseFloat(totalPuntosZonas) || 0
  const completo = Math.round(total) === 100

  const handleNuevaZona = async () => {
    if (!nuevaZona.nombre.trim() || !nuevaZona.puntos) {
      setAlert('El nombre y los puntos de la zona son obligatorios')
      return
    }
    setGuardandoZona(true)
    try {
      await api.post('/v1/zonas', {
        id_asignacion: parseInt(idAsignacion),
        nombre: nuevaZona.nombre.trim(),
        puntos: parseFloat(nuevaZona.puntos),
        posicion: zonas.length,
      })
      setNuevaZona({ nombre: '', puntos: '' })
      reload()
    } catch (err) {
      setAlert(err.response?.data?.errors?.puntos?.[0] || err.response?.data?.message || 'Error al crear la zona')
    } finally {
      setGuardandoZona(false)
    }
  }

  const guardarZona = async (idZona) => {
    if (!zonaForm.nombre.trim() || !zonaForm.puntos) {
      setAlert('El nombre y los puntos de la zona son obligatorios')
      return
    }
    try {
      await api.put(`/v1/zonas/${idZona}`, {
        nombre: zonaForm.nombre.trim(),
        puntos: parseFloat(zonaForm.puntos),
      })
      setZonaEditando(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.errors?.puntos?.[0] || err.response?.data?.message || 'Error al guardar la zona')
    }
  }

  const eliminarZona = async (zona) => {
    try {
      await api.delete(`/v1/zonas/${zona.id_zona}`)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al eliminar la zona')
    }
  }

  const abrirNuevaActividad = (idZona) => {
    setModalActividad('crear')
    setActividadForm({ id_zona: idZona || '', nombre: '', porcentaje: '' })
  }

  const abrirEditarActividad = (actividad, idZona) => {
    setModalActividad('editar')
    setActividadForm({ id_evaluacion: actividad.id_evaluacion, id_zona: idZona || actividad.id_zona || '', nombre: actividad.nombre || '', porcentaje: actividad.porcentaje || '' })
  }

  const guardarActividad = async () => {
    if (!actividadForm.nombre.trim() || !actividadForm.porcentaje) {
      setAlert('El nombre y los puntos de la actividad son obligatorios')
      return
    }
    setGuardandoActividad(true)
    const payload = {
      nombre: actividadForm.nombre.trim(),
      porcentaje: parseFloat(actividadForm.porcentaje),
      id_zona: actividadForm.id_zona ? parseInt(actividadForm.id_zona) : null,
    }
    try {
      if (modalActividad === 'editar') {
        await api.put(`/v1/evaluaciones/${actividadForm.id_evaluacion}`, payload)
      } else {
        await api.post(`/v1/registro-calificaciones/${idAsignacion}/evaluaciones`, payload)
      }
      setModalActividad(null)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.errors?.id_zona?.[0] || err.response?.data?.message || 'Error al guardar la actividad')
    } finally {
      setGuardandoActividad(false)
    }
  }

  const eliminarActividad = async (idEvaluacion) => {
    try {
      await api.delete(`/v1/registro-calificaciones/evaluaciones/${idEvaluacion}`)
      reload()
    } catch (err) {
      setAlert(err.response?.data?.message || 'Error al eliminar la actividad')
    }
  }

  return (
    <div className="space-y-6">
      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
          <div>
            <h3 className="text-sm font-bold text-neutral-700 dark:text-neutral-200">
              Estructura de evaluación
            </h3>
            <p className="text-xs text-neutral-400">
              Distribuye los 100 puntos en zonas (tareas, parciales, laboratorios, etc.)
            </p>
          </div>
          <span className={`inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold ${completo ? badge.success : badge.warning}`}>
            Total: {total} / 100 pts
          </span>
        </div>

        <div className="grid grid-cols-1 gap-4 p-6 sm:grid-cols-[1fr_140px_auto]">
          <div>
            <label className={input.label}>Nombre de la zona</label>
            <input
              type="text"
              value={nuevaZona.nombre}
              onChange={(e) => setNuevaZona({ ...nuevaZona, nombre: e.target.value })}
              placeholder="Ej. Tareas, Parciales, Laboratorios..."
              className={input.base}
            />
          </div>
          <div>
            <label className={input.label}>Puntos (sobre 100)</label>
            <input
              type="number"
              min="0"
              max="100"
              value={nuevaZona.puntos}
              onChange={(e) => setNuevaZona({ ...nuevaZona, puntos: e.target.value })}
              placeholder="30"
              className={input.base}
            />
          </div>
          <div className="flex items-end">
            <button onClick={handleNuevaZona} disabled={guardandoZona} className={`${btn.primary} w-full disabled:opacity-50`}>
              {guardandoZona ? 'Agregando...' : 'Agregar zona'}
            </button>
          </div>
        </div>
      </div>

      {zonas.length === 0 && sinZona.length === 0 ? (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <p className="text-sm text-neutral-400 dark:text-neutral-500">
            Aún no has definido zonas. Agrega la primera zona para comenzar a estructurar los 100 puntos.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {zonas.map((zona, idx) => {
            const sumActividades = zona.evaluaciones.reduce((acc, ev) => acc + (parseFloat(ev.porcentaje) || 0), 0)
            const editando = zonaEditando === zona.id_zona
            return (
              <div key={zona.id_zona} className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
                <div className={`flex flex-wrap items-center justify-between gap-3 border-b px-6 py-4 ${sumActividades === parseFloat(zona.puntos) ? 'border-neutral-100 bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-700/50' : 'border-warning-200 bg-warning-50 dark:border-warning-700 dark:bg-warning-900/20'}`}>
                  <div className="flex min-w-0 items-center gap-3">
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">{idx + 1}</span>
                    {editando ? (
                      <div className="flex flex-wrap items-center gap-2">
                        <input
                          type="text"
                          value={zonaForm.nombre}
                          onChange={(e) => setZonaForm({ ...zonaForm, nombre: e.target.value })}
                          className={`${input.base} !w-48`}
                        />
                        <input
                          type="number"
                          min="0"
                          max="100"
                          value={zonaForm.puntos}
                          onChange={(e) => setZonaForm({ ...zonaForm, puntos: e.target.value })}
                          className={`${input.base} !w-24`}
                        />
                        <button onClick={() => guardarZona(zona.id_zona)} className={btn.primary}>Guardar</button>
                        <button onClick={() => setZonaEditando(null)} className={btn.neutral}>Cancelar</button>
                      </div>
                    ) : (
                      <h4 className="text-base font-bold text-neutral-800 dark:text-neutral-100">{zona.nombre}</h4>
                    )}
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`rounded-full px-3 py-1 text-xs font-bold ${sumActividades === parseFloat(zona.puntos) ? badge.success : badge.warning}`}>
                      {sumActividades} / {zona.puntos} pts
                    </span>
                    {!editando && (
                      <>
                        <button
                          onClick={() => {
                            setZonaEditando(zona.id_zona)
                            setZonaForm({ nombre: zona.nombre, puntos: zona.puntos })
                          }}
                          className={`${btn.outline} !px-3 !py-1.5`}
                          title="Editar zona"
                        >
                          Editar
                        </button>
                        <button onClick={() => eliminarZona(zona)} className={`${btn.outlineDanger} !px-3 !py-1.5`} title="Eliminar zona">
                          Eliminar
                        </button>
                      </>
                    )}
                  </div>
                </div>

                <div className="p-6">
                  {zona.evaluaciones.length === 0 ? (
                    <p className="mb-4 text-sm text-neutral-400 dark:text-neutral-500">
                      Sin actividades. Agrega las actividades que sumen {zona.puntos} pts.
                    </p>
                  ) : (
                    <ul className="mb-4 divide-y divide-neutral-100 dark:divide-neutral-700">
                      {zona.evaluaciones.map((ev) => (
                        <li key={ev.id_evaluacion} className="flex items-center justify-between gap-3 py-2.5">
                          <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-200">{ev.nombre}</span>
                          <span className="flex items-center gap-3">
                            <span className="text-sm font-bold text-primary">{ev.porcentaje} pts</span>
                            <button onClick={() => abrirEditarActividad(ev, zona.id_zona)} className="text-xs font-semibold text-primary hover:text-primary-accent-300">
                              Editar
                            </button>
                            <button onClick={() => eliminarActividad(ev.id_evaluacion)} className="text-xs font-semibold text-danger hover:opacity-75">
                              Eliminar
                            </button>
                          </span>
                        </li>
                      ))}
                    </ul>
                  )}
                  <button onClick={() => abrirNuevaActividad(zona.id_zona)} className={`${btn.outline} !px-3 !py-1.5`}>
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Agregar actividad
                  </button>
                </div>
              </div>
            )
          })}

          {sinZona.length > 0 && (
            <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
              <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
                <h4 className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Actividades sin zona asignada ({sinZona.length})</h4>
              </div>
              <div className="p-6">
                <ul className="divide-y divide-neutral-100 dark:divide-neutral-700">
                  {sinZona.map((ev) => (
                    <li key={ev.id_evaluacion} className="flex items-center justify-between gap-3 py-2.5">
                      <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-200">{ev.nombre}</span>
                      <span className="flex items-center gap-3">
                        <span className="text-sm font-bold text-primary">{ev.porcentaje} pts</span>
                        <button onClick={() => abrirEditarActividad(ev, null)} className="text-xs font-semibold text-primary hover:text-primary-accent-300">
                          Editar
                        </button>
                        <button onClick={() => eliminarActividad(ev.id_evaluacion)} className="text-xs font-semibold text-danger hover:opacity-75">
                          Eliminar
                        </button>
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          )}
        </div>
      )}

      <Modal
        open={!!modalActividad}
        onClose={() => setModalActividad(null)}
        title={modalActividad === 'editar' ? 'Editar actividad' : 'Nueva actividad'}
        size="md"
        footer={
          <>
            <button onClick={() => setModalActividad(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={guardarActividad} disabled={guardandoActividad} className={`${btn.primary} disabled:opacity-50`}>
              {guardandoActividad ? 'Guardando...' : 'Guardar'}
            </button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className={input.label}>Zona</label>
            <select value={actividadForm.id_zona} onChange={(e) => setActividadForm({ ...actividadForm, id_zona: e.target.value })} className={input.base}>
              <option value="">Sin zona</option>
              {zonas.map((z) => (
                <option key={z.id_zona} value={z.id_zona}>
                  {z.nombre} ({z.puntos} pts)
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={input.label}>Nombre de la actividad</label>
            <input type="text" value={actividadForm.nombre} onChange={(e) => setActividadForm({ ...actividadForm, nombre: e.target.value })} placeholder="Ej. Parcial I, Tarea 1, Práctica de laboratorio..." className={input.base} />
          </div>
          <div>
            <label className={input.label}>Puntos</label>
            <input type="number" min="0" max="100" value={actividadForm.porcentaje} onChange={(e) => setActividadForm({ ...actividadForm, porcentaje: e.target.value })} placeholder="Ej. 15" className={input.base} />
            <p className="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Puntos que vale esta actividad dentro de su zona.</p>
          </div>
        </div>
      </Modal>
    </div>
  )
}
