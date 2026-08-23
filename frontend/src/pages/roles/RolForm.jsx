import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import FormInput from '../../components/FormInput'
import api from '../../api/axios'
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

export default function RolForm() {
  const { id } = useParams(); const isEdit = !!id; const navigate = useNavigate()
  const [form, setForm] = useState({ nombre: '', descripcion: '' })
  const [permisos, setPermisos] = useState([])
  const [selected, setSelected] = useState([])
  const [saving, setSaving] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    api.get('/v1/permisos').then((r) => setPermisos(r.data || [])).catch(console.error)
  }, [])

  useEffect(() => {
    if (isEdit) api.get(`/v1/roles/${id}`).then((r) => {
      setForm({ nombre: r.data.nombre || '', descripcion: r.data.descripcion || '' })
      setSelected((r.data.permisos || []).map((p) => p.id_permiso))
    }).catch(console.error)
  }, [id, isEdit])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const togglePermiso = (idPermiso) => {
    setSelected((prev) =>
      prev.includes(idPermiso) ? prev.filter((x) => x !== idPermiso) : [...prev, idPermiso]
    )
  }

  const toggleModulo = (modPermisos) => {
    const ids = modPermisos.map((p) => p.id_permiso)
    const allSelected = ids.every((x) => selected.includes(x))
    setSelected((prev) =>
      allSelected ? prev.filter((x) => !ids.includes(x)) : [...new Set([...prev, ...ids])]
    )
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setAlertMessage(null)
    try {
      let rolId = id
      if (isEdit) {
        await api.put(`/v1/roles/${id}`, form)
      } else {
        const res = await api.post('/v1/roles', form)
        rolId = res.data.id_rol
      }
      await api.post(`/v1/roles/${rolId}/permisos`, { permiso_ids: selected })
      navigate('/roles')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al guardar')
      setSaving(false)
    }
  }

  const groups = groupByModule(permisos)
  const modules = Object.keys(groups)

  return (
    <div className="max-w-4xl mx-auto">
      <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">{isEdit ? 'Editar' : 'Nuevo'} Rol</h1>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="rounded-xl bg-white p-6 shadow-4 space-y-4 dark:bg-surface-dark">
          <FormInput label="Nombre" name="nombre" value={form.nombre} onChange={handleChange} required />
          <FormInput label="Descripción" name="descripcion" type="textarea" value={form.descripcion} onChange={handleChange} />
        </div>

        <div className="rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">Permisos del Rol</h2>
              <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                Marca las acciones permitidas para este rol. Se aplican al menú y a la API.
              </p>
            </div>
            <button
              type="button"
              data-twe-ripple-init
              onClick={() => setSelected(permisos.map((p) => p.id_permiso))}
              className="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10"
            >
              Seleccionar todos
            </button>
          </div>

          {modules.length === 0 ? (
            <div className="py-8 text-center text-sm font-medium text-neutral-400">
              Cargando permisos...
            </div>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2">
              {modules.map((modulo) => {
                const modPermisos = groups[modulo]
                const ids = modPermisos.map((p) => p.id_permiso)
                const allSelected = ids.every((x) => selected.includes(x))
                const someSelected = ids.some((x) => selected.includes(x))
                return (
                  <div key={modulo} className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-600">
                    <div className="flex items-center justify-between border-b border-neutral-100 bg-neutral-50 px-4 py-2.5 dark:border-neutral-600 dark:bg-neutral-700/50">
                      <span className="text-sm font-bold uppercase tracking-wide text-neutral-700 dark:text-neutral-200">
                        {modulo}
                      </span>
                      <label className="flex cursor-pointer items-center gap-2 text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                        <input
                          type="checkbox"
                          checked={allSelected}
                          ref={(el) => { if (el) el.indeterminate = !allSelected && someSelected }}
                          onChange={() => toggleModulo(modPermisos)}
                          className="h-4 w-4 rounded border-neutral-300 text-primary accent-primary focus:ring-primary dark:border-neutral-500 dark:bg-neutral-800"
                        />
                        Todos
                      </label>
                    </div>
                    <div className="flex flex-wrap gap-3 px-4 py-3">
                      {modPermisos.map((p) => (
                        <label key={p.id_permiso} className="flex cursor-pointer items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                          <input
                            type="checkbox"
                            checked={selected.includes(p.id_permiso)}
                            onChange={() => togglePermiso(p.id_permiso)}
                            className="h-4 w-4 rounded border-neutral-300 text-primary accent-primary focus:ring-primary dark:border-neutral-500 dark:bg-neutral-800"
                          />
                          {accionLabel[p.accion] || p.accion}
                        </label>
                      ))}
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </div>

        <div className="flex gap-3">
          <button type="submit" disabled={saving} className={`${btn.primary} disabled:cursor-not-allowed disabled:opacity-60`}>
            {saving ? 'Guardando...' : isEdit ? 'Actualizar Rol' : 'Crear Rol'}
          </button>
          <button type="button" onClick={() => navigate('/roles')} className={btn.neutral}>Cancelar</button>
        </div>
      </form>

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
        <p className="text-sm text-neutral-600 dark:text-neutral-300 whitespace-pre-line">{alertMessage}</p>
      </Modal>
    </div>
  )
}
