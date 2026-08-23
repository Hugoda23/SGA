import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function SeccionList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmAction, setConfirmAction] = useState(null)
  const [search, setSearch] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ nombre: '' })
  const [editItem, setEditItem] = useState(null)

  const fetchData = async () => {
    try {
      const r = await api.get('/v1/secciones')
      setData(r.data)
    } catch (e) {
      console.error(e)
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { fetchData() }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await api.post('/v1/secciones', form)
      setShowForm(false)
      setForm({ nombre: '' })
      fetchData()
      setAlertMessage('Sección creada exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al crear sección')
    }
  }

  const handleUpdate = async (e) => {
    e.preventDefault()
    try {
      await api.put(`/v1/secciones/${editItem.id_seccion}`, { nombre: editItem.nombre })
      setEditItem(null)
      fetchData()
      setAlertMessage('Sección actualizada exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al actualizar')
    }
  }

  const handleDelete = (row) => {
    setConfirmAction({
      message: `¿Estás seguro de eliminar la sección "${row.nombre}"? Esto afectará asignaciones relacionadas.`,
      onConfirm: async () => {
        try {
          await api.delete(`/v1/secciones/${row.id_seccion}`)
          fetchData()
          setAlertMessage('Sección eliminada correctamente.')
        } catch (err) {
          setAlertMessage(err.response?.data?.message || 'Error al eliminar')
        }
      }
    })
  }

  const filteredData = data.filter(e =>
    e.nombre.toLowerCase().includes(search.toLowerCase())
  )

  const LETTER_COLORS = ['bg-primary-50 text-primary', 'bg-success-50 text-success', 'bg-info-50 text-info', 'bg-warning-50 text-warning', 'bg-danger-50 text-danger', 'bg-neutral-100 text-neutral-600']
  const getColor = (nombre) => LETTER_COLORS[(nombre.charCodeAt(0) - 65) % LETTER_COLORS.length]

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Gestión de Secciones</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Administra las secciones (A, B, C...) disponibles para las asignaciones.</p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className={btn.primary}
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Nueva Sección
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
              placeholder="Buscar sección..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
          <span className="text-xs font-semibold text-neutral-400">{filteredData.length} encontrada(s)</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>SECCIÓN</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="2" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Cargando secciones...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredData.length === 0 ? (
                <tr>
                  <td colSpan="2" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay secciones registradas</span>
                      <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo registro.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                filteredData.map((row) => (
                  <tr key={row.id_seccion} className={tbl.row}>
                    <td className={tbl.td}>
                      <span className={`inline-flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold ${getColor(row.nombre)}`}>
                        {row.nombre}
                      </span>
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
        title="Nueva Sección"
        size="sm"
        footer={
          <>
            <button type="submit" form="form-seccion-create" className={btn.primary}>Crear</button>
            <button type="button" onClick={() => { setShowForm(false); setForm({ nombre: '' }) }} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-seccion-create" onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre *</label>
            <input
              name="nombre"
              value={form.nombre}
              onChange={(e) => setForm({ nombre: e.target.value })}
              required
              placeholder="Ej: A, B, C..."
              maxLength={20}
              className={input.base}
            />
          </div>
        </form>
      </Modal>

      {editItem && (
      <Modal
        open
        onClose={() => setEditItem(null)}
        title="Editar Sección"
        size="sm"
        footer={
          <>
            <button type="submit" form="form-seccion-edit" className={btn.primary}>Guardar Cambios</button>
            <button type="button" onClick={() => setEditItem(null)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-seccion-edit" onSubmit={handleUpdate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre *</label>
            <input
              value={editItem.nombre}
              onChange={(e) => setEditItem({ ...editItem, nombre: e.target.value })}
              required
              maxLength={20}
              className={input.base}
            />
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
