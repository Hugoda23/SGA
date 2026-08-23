import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function AulaList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmAction, setConfirmAction] = useState(null)
  const [search, setSearch] = useState('')
  
  const [edificios, setEdificios] = useState([])
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ nombre_aula: '', capacidad: '', id_edificio: '' })
  const [editItem, setEditItem] = useState(null)
  
  const fetchData = async () => { 
    try { 
      const r = await api.get('/v1/aulas')
      setData(r.data) 
    } catch(e) { 
      console.error(e) 
    } finally { 
      setLoading(false) 
    } 
  }

  const fetchEdificios = async () => {
    try {
      const r = await api.get('/v1/edificios')
      setEdificios(r.data)
    } catch (e) {
      console.error(e)
    }
  }

  useEffect(() => { 
    fetchData()
    fetchEdificios() 
  }, [])

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await api.post('/v1/aulas', form)
      setShowForm(false)
      setForm({ nombre_aula: '', capacidad: '', id_edificio: '' })
      fetchData()
      setAlertMessage('Aula creada exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al crear aula')
    }
  }

  const handleUpdate = async (e) => {
    e.preventDefault()
    try {
      await api.put(`/v1/aulas/${editItem.id_aula}`, { 
        nombre_aula: editItem.nombre_aula, 
        capacidad: editItem.capacidad,
        id_edificio: editItem.id_edificio
      })
      setEditItem(null)
      fetchData()
      setAlertMessage('Aula actualizada exitosamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al actualizar')
    }
  }

  const handleDelete = (row) => {
    setConfirmAction({
      message: `¿Estás seguro de que deseas eliminar permanentemente el aula "${row.nombre_aula}"?`,
      onConfirm: async () => {
        try { 
          await api.delete(`/v1/aulas/${row.id_aula}`); 
          fetchData(); 
          setAlertMessage('Aula eliminada correctamente.');
        } catch (err) { 
          setAlertMessage(err.response?.data?.message || 'Error al eliminar');
        }
      }
    });
  }

  const filteredData = data.filter(a => a.nombre_aula.toLowerCase().includes(search.toLowerCase()) || a.edificio?.nombre.toLowerCase().includes(search.toLowerCase()))

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Gestión de Aulas</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Administra los espacios físicos disponibles para impartir clases.</p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className={btn.primary}
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Nueva Aula
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
              placeholder="Buscar por aula o edificio..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>AULA</th>
                <th className={tbl.th}>CAPACIDAD</th>
                <th className={tbl.th}>EDIFICIO</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="4" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Cargando aulas...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredData.length === 0 ? (
                <tr>
                  <td colSpan="4" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay aulas registradas</span>
                      <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo registro.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                filteredData.map((row) => (
                  <tr key={row.id_aula} className={tbl.row}>
                    <td className={tbl.td}>
                      <span className="font-medium text-neutral-700">{row.nombre_aula}</span>
                    </td>
                    <td className={tbl.td}>
                      {row.capacidad} estudiantes
                    </td>
                    <td className={tbl.td}>
                      <span className={badge.neutral}>
                        {row.edificio?.nombre || '-'}
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
        title="Nueva Aula"
        size="md"
        footer={
          <>
            <button type="submit" form="form-aula-create" className={btn.primary}>Crear</button>
            <button type="button" onClick={() => setShowForm(false)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-aula-create" onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre del Aula *</label>
            <input name="nombre_aula" value={form.nombre_aula} onChange={handleChange} required className={input.base} />
          </div>
          <div>
            <label className={input.label}>Capacidad *</label>
            <input type="number" name="capacidad" value={form.capacidad} onChange={handleChange} required min="1" className={input.base} />
          </div>
          <div>
            <label className={input.label}>Edificio *</label>
            <select name="id_edificio" value={form.id_edificio} onChange={handleChange} required className={input.base}>
              <option value="">Seleccione un edificio</option>
              {edificios.map(e => (
                <option key={e.id_edificio} value={e.id_edificio}>{e.nombre}</option>
              ))}
            </select>
          </div>
        </form>
      </Modal>

      {editItem && (
      <Modal
        open
        onClose={() => setEditItem(null)}
        title="Editar Aula"
        size="md"
        footer={
          <>
            <button type="submit" form="form-aula-edit" className={btn.primary}>Guardar Cambios</button>
            <button type="button" onClick={() => setEditItem(null)} className={btn.neutral}>Cancelar</button>
          </>
        }
      >
        <form id="form-aula-edit" onSubmit={handleUpdate} className="space-y-4">
          <div>
            <label className={input.label}>Nombre del Aula *</label>
            <input value={editItem.nombre_aula} onChange={(e) => setEditItem({...editItem, nombre_aula: e.target.value})} required className={input.base} />
          </div>
          <div>
            <label className={input.label}>Capacidad *</label>
            <input type="number" value={editItem.capacidad} onChange={(e) => setEditItem({...editItem, capacidad: e.target.value})} required min="1" className={input.base} />
          </div>
          <div>
            <label className={input.label}>Edificio *</label>
            <select value={editItem.id_edificio || ''} onChange={(e) => setEditItem({...editItem, id_edificio: e.target.value})} required className={input.base}>
              <option value="">Seleccione un edificio</option>
              {edificios.map(e => (
                <option key={e.id_edificio} value={e.id_edificio}>{e.nombre}</option>
              ))}
            </select>
          </div>
        </form>
      </Modal>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Mensaje del Sistema"
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
              onClick={() => {
                confirmAction.onConfirm();
                setConfirmAction(null);
              }}
              className={btn.danger}
            >
              Sí, Confirmar
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
