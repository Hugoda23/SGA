import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function GradoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmAction, setConfirmAction] = useState(null)
  const [search, setSearch] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ nombre: '', nivel: '' })
  const [editItem, setEditItem] = useState(null)

  const NIVELES = ['Preprimaria', 'Primaria', 'Básico', 'Diversificado']

  const fetchData = async () => {
    try {
      const r = await api.get('/v1/grados')
      setData(r.data)
    } catch (e) {
      console.error(e)
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { fetchData() }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await api.post('/v1/grados', form)
      setShowForm(false)
      setForm({ nombre: '', nivel: '' })
      fetchData()
      setAlertMessage('Grado creado exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al crear grado')
    }
  }

  const handleUpdate = async (e) => {
    e.preventDefault()
    try {
      await api.put(`/v1/grados/${editItem.id_grado}`, { nombre: editItem.nombre, nivel: editItem.nivel })
      setEditItem(null)
      fetchData()
      setAlertMessage('Grado actualizado exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al actualizar')
    }
  }

  const handleDelete = (row) => {
    setConfirmAction({
      message: `¿Estás seguro de eliminar el grado "${row.nombre}"? Esto afectará asignaciones relacionadas.`,
      onConfirm: async () => {
        try {
          await api.delete(`/v1/grados/${row.id_grado}`)
          fetchData()
          setAlertMessage('Grado eliminado correctamente.')
        } catch (err) {
          setAlertMessage(err.response?.data?.message || 'Error al eliminar')
        }
      }
    })
  }

  const filteredData = data.filter(e =>
    e.nombre.toLowerCase().includes(search.toLowerCase()) ||
    (e.nivel || '').toLowerCase().includes(search.toLowerCase())
  )

  const NIVEL_COLORS = {
    'Preprimaria': badge.warning,
    'Primaria': badge.success,
    'Básico': badge.primary,
    'Diversificado': badge.info,
  }

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Gestión de Grados</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Administra los grados académicos disponibles en el sistema.</p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className={btn.primary}
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Nuevo Grado
        </button>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por nombre o nivel..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
          <span className="text-xs font-semibold text-neutral-400">{filteredData.length} encontrado(s)</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>NOMBRE</th>
                <th className={tbl.th}>NIVEL</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="3" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Cargando grados...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredData.length === 0 ? (
                <tr>
                  <td colSpan="3" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay grados registrados</span>
                      <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo registro.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                filteredData.map((row) => (
                  <tr key={row.id_grado} className={tbl.row}>
                    <td className={tbl.td}>
                      <span className="font-medium text-neutral-700">{row.nombre}</span>
                    </td>
                    <td className={tbl.td}>
                      {row.nivel ? (
                        <span className={NIVEL_COLORS[row.nivel] || badge.neutral}>
                          {row.nivel}
                        </span>
                      ) : (
                        <span className="text-neutral-400">—</span>
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => setEditItem(row)}
                          className="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10"
                        >
                          Editar
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(row)}
                          className="rounded-lg bg-danger-50 px-3 py-1.5 text-xs font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
                        >
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <Modal
        open={showForm}
        onClose={() => setShowForm(false)}
        title="Nuevo Grado"
        size="md"
        footer={
          <>
            <button type="submit" form="form-grado-create" className={btn.primary}>Crear</button>
            <button type="button" onClick={() => { setShowForm(false); setForm({ nombre: '', nivel: '' }) }} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-grado-create" onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre *</label>
            <input name="nombre" value={form.nombre} onChange={handleChange} required placeholder="Ej: Primero, Segundo, 4to..." className={input.base} />
          </div>
          <div>
            <label className={input.label}>Nivel Educativo</label>
            <select name="nivel" value={form.nivel} onChange={handleChange} className={input.base}>
              <option value="">— Sin especificar —</option>
              {NIVELES.map(n => <option key={n} value={n}>{n}</option>)}
            </select>
          </div>
        </form>
      </Modal>

      {editItem && (
      <Modal
        open
        onClose={() => setEditItem(null)}
        title="Editar Grado"
        size="md"
        footer={
          <>
            <button type="submit" form="form-grado-edit" className={btn.primary}>Guardar Cambios</button>
            <button type="button" onClick={() => setEditItem(null)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-grado-edit" onSubmit={handleUpdate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre *</label>
            <input value={editItem.nombre} onChange={(e) => setEditItem({ ...editItem, nombre: e.target.value })} required className={input.base} />
          </div>
          <div>
            <label className={input.label}>Nivel Educativo</label>
            <select value={editItem.nivel || ''} onChange={(e) => setEditItem({ ...editItem, nivel: e.target.value })} className={input.base}>
              <option value="">— Sin especificar —</option>
              {NIVELES.map(n => <option key={n} value={n}>{n}</option>)}
            </select>
          </div>
        </form>
      </Modal>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>

      {confirmAction && (
      <Modal
        open
        onClose={() => setConfirmAction(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmAction(null)} className={btn.ghost}>Cancelar</button>
            <button
              type="button"
              onClick={() => { confirmAction.onConfirm(); setConfirmAction(null) }}
              className={btn.danger}
            >
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{confirmAction.message}</p>
      </Modal>
      )}
    </div>
  )
}
