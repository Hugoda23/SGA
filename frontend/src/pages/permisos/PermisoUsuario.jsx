import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import SearchableSelect from '../../components/SearchableSelect'
import { btn } from '../../lib/twClasses'
import Modal from '../../components/Modal'

function groupByModule(permisos) {
  const groups = {}
  permisos.forEach((p) => {
    const dot = p.nombre.lastIndexOf('.')
    const modulo = dot === -1 ? 'general' : p.nombre.slice(0, dot)
    const accion = dot === -1 ? p.nombre : p.nombre.slice(dot + 1)
    if (!groups[modulo]) groups[modulo] = []
    groups[modulo].push({ ...p, accion })
  })
  return groups
}

const accionLabel = {
  ver: 'Ver',
  crear: 'Crear',
  editar: 'Editar',
  eliminar: 'Eliminar',
  asignar: 'Asignar',
  registrar: 'Registrar',
  calificar: 'Calificar',
  subir: 'Subir',
  descargar: 'Descargar',
  generar: 'Generar',
}

export default function PermisoUsuario() {
  const navigate = useNavigate()
  const [usuarios, setUsuarios] = useState([])
  const [idUsuario, setIdUsuario] = useState('')
  const [permisos, setPermisos] = useState([])
  const [estado, setEstado] = useState({})
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    api.get('/v1/usuarios').then((r) => setUsuarios(normList(r.data).map((u) => ({
      value: u.id_usuario,
      label: `${u.username} — ${(u.roles || []).map((rol) => rol.nombre).join(', ') || 'sin rol'}`,
    }))))
  }, [])

  const cargarPermisos = useCallback((id) => {
    if (!id) { setPermisos([]); setEstado({}); return }
    setLoading(true)
    api.get(`/v1/usuarios/${id}/permisos`).then((r) => {
      setPermisos(r.data)
      setEstado(Object.fromEntries(r.data.map((p) => [p.id_permiso, p.efectivo])))
    }).catch(console.error).finally(() => setLoading(false))
  }, [])

  const handleSeleccionar = (e) => {
    const id = e.target.value
    setIdUsuario(id)
    cargarPermisos(id)
  }

  const togglePermiso = (idPermiso) => {
    setEstado((prev) => ({ ...prev, [idPermiso]: !prev[idPermiso] }))
  }

  const toggleModulo = (modPermisos, activar) => {
    setEstado((prev) => {
      const next = { ...prev }
      modPermisos.forEach((p) => { next[p.id_permiso] = activar })
      return next
    })
  }

  const quitarExcepciones = () => {
    setEstado(Object.fromEntries(permisos.map((p) => [p.id_permiso, p.heredado])))
  }

  const handleGuardar = async () => {
    setSaving(true)
    setAlertMessage(null)
    try {
      const overrides = permisos
        .filter((p) => estado[p.id_permiso] !== p.heredado)
        .map((p) => ({ id_permiso: p.id_permiso, concedido: estado[p.id_permiso] }))

      await api.put(`/v1/usuarios/${idUsuario}/permisos`, { overrides })
      cargarPermisos(idUsuario)
      setAlertMessage({ type: 'success', text: 'Permisos del usuario actualizados correctamente.' })
    } catch (err) {
      setAlertMessage({ type: 'error', text: err.response?.data?.message || 'Error al guardar' })
    } finally {
      setSaving(false)
    }
  }

  const groups = groupByModule(permisos)
  const modules = Object.keys(groups)
  const hayExcepciones = permisos.some((p) => estado[p.id_permiso] !== p.heredado)

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">Permisos por Usuario</h1>
        <button type="button" onClick={() => navigate('/permisos')} className={btn.neutral}>Volver a Permisos</button>
      </div>

      <div className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark mb-6">
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          Elegí un usuario para ver y ajustar qué vistas y acciones puede usar, más allá de lo que le da su rol. Por
          ejemplo, aunque el rol "director" no incluya ver los logs del sistema, acá podés activárselo solo a un
          usuario puntual (o quitarle algo que su rol sí le da).
        </p>
        <SearchableSelect
          label="Usuario"
          name="id_usuario"
          value={idUsuario}
          onChange={handleSeleccionar}
          options={usuarios}
          placeholder="Buscar usuario..."
        />
      </div>

      {idUsuario && (
        <div className="rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">Vistas y permisos</h2>
              <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                Marcado en <span className="font-bold text-primary">azul</span> = excepción propia del usuario, distinta a lo que le da su rol.
              </p>
            </div>
            {hayExcepciones && (
              <button type="button" onClick={quitarExcepciones} className="rounded-lg bg-neutral-100 px-3 py-1.5 text-xs font-bold text-neutral-600 transition-colors hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-300">
                Quitar todas las excepciones
              </button>
            )}
          </div>

          {loading || modules.length === 0 ? (
            <div className="py-8 text-center text-sm font-medium text-neutral-400">
              {loading ? 'Cargando permisos...' : 'Sin permisos que mostrar.'}
            </div>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2">
              {modules.map((modulo) => {
                const modPermisos = groups[modulo]
                return (
                  <div key={modulo} className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-600">
                    <div className="flex items-center justify-between border-b border-neutral-100 bg-neutral-50 px-4 py-2.5 dark:border-neutral-600 dark:bg-neutral-700/50">
                      <span className="text-sm font-bold uppercase tracking-wide text-neutral-700 dark:text-neutral-200">
                        {modulo}
                      </span>
                      <div className="flex gap-2 text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                        <button type="button" onClick={() => toggleModulo(modPermisos, true)} className="hover:text-primary">Todos</button>
                        <span>·</span>
                        <button type="button" onClick={() => toggleModulo(modPermisos, false)} className="hover:text-danger">Ninguno</button>
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-3 px-4 py-3">
                      {modPermisos.map((p) => {
                        const esExcepcion = estado[p.id_permiso] !== p.heredado
                        return (
                          <label key={p.id_permiso} className={`flex cursor-pointer items-center gap-2 text-sm font-medium ${esExcepcion ? 'text-primary font-bold' : 'text-neutral-700 dark:text-neutral-300'}`}>
                            <input
                              type="checkbox"
                              checked={!!estado[p.id_permiso]}
                              onChange={() => togglePermiso(p.id_permiso)}
                              className="h-4 w-4 rounded border-neutral-300 text-primary accent-primary focus:ring-primary dark:border-neutral-500 dark:bg-neutral-800"
                            />
                            {accionLabel[p.accion] || p.accion}
                          </label>
                        )
                      })}
                    </div>
                  </div>
                )
              })}
            </div>
          )}

          <div className="mt-6 flex gap-3">
            <button type="button" onClick={handleGuardar} disabled={saving} className={`${btn.primary} disabled:cursor-not-allowed disabled:opacity-60`}>
              {saving ? 'Guardando...' : 'Guardar permisos del usuario'}
            </button>
          </div>
        </div>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button className={`${btn.primary} w-full`} onClick={() => setAlertMessage(null)}>
            Aceptar
          </button>
        }
      >
        <p className={`text-sm whitespace-pre-line ${alertMessage?.type === 'error' ? 'text-danger' : 'text-neutral-600 dark:text-neutral-300'}`}>
          {alertMessage?.text}
        </p>
      </Modal>
    </div>
  )
}
