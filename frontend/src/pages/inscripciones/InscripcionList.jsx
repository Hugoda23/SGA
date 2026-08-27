import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'
import Modal from '../../components/Modal'
import SearchableSelect from '../../components/SearchableSelect'
import { btn } from '../../lib/twClasses'

export default function InscripcionList() {
  const [data, setData] = useState([]); const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const [gestionAlumno, setGestionAlumno] = useState(null)
  const [confirmarEliminar, setConfirmarEliminar] = useState(null)
  const [editAlumno, setEditAlumno] = useState(null)
  const [savingEdit, setSavingEdit] = useState(false)
  const [carreras, setCarreras] = useState([])
  const [grados, setGrados] = useState([])
  const [secciones, setSecciones] = useState([])
  const [alertMessage, setAlertMessage] = useState(null)
  const navigate = useNavigate()

  const fetchData = useCallback(async () => {
    setLoading(true)
    try { const r = await api.get('/v1/inscripciones/resumen-alumnos', { params: { page, q: q || undefined } }); setData(r.data); setPagination({ current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }) } catch(e) { console.error(e) } finally { setLoading(false) }
  }, [page, q])
  useEffect(() => { fetchData() }, [fetchData])

  useEffect(() => {
    api.get('/v1/carreras').then((r) => setCarreras([
      { value: '', label: '— Sin carrera —' },
      ...r.data.map((c) => ({ value: c.id_carrera, label: c.nombre_carrera })),
    ]))
    api.get('/v1/grados').then((r) => setGrados(r.data.map((g) => ({ value: g.id_grado, label: g.nivel ? `${g.nombre} — ${g.nivel}` : g.nombre }))))
    api.get('/v1/secciones').then((r) => setSecciones([
      { value: '', label: '— Sin sección —' },
      ...r.data.map((s) => ({ value: s.id_seccion, label: s.nombre })),
    ]))
  }, [])

  const eliminarInscripcion = async (idInscripcion) => {
    try {
      await api.delete(`/v1/inscripciones/${idInscripcion}`)
      setGestionAlumno((prev) => prev ? { ...prev, inscripciones: prev.inscripciones.filter((i) => i.id_inscripcion !== idInscripcion) } : prev)
      fetchData()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'No se pudo eliminar la inscripción.')
    } finally {
      setConfirmarEliminar(null)
    }
  }

  const guardarEdicion = async (e) => {
    e.preventDefault()
    setSavingEdit(true)
    try {
      await api.put(`/v1/alumnos/${editAlumno.id_alumno}`, {
        id_carrera: editAlumno.id_carrera || null,
        id_grado_actual: editAlumno.id_grado_actual || null,
        id_seccion_actual: editAlumno.id_seccion_actual || null,
      })
      setEditAlumno(null)
      fetchData()
      setAlertMessage('Datos del alumno actualizados correctamente.')
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'No se pudo actualizar el alumno.')
    } finally {
      setSavingEdit(false)
    }
  }

  const columns = [
    { key: 'alumno', label: 'Alumno', render: (row) => `${row.nombre} ${row.apellido}`, exportValue: (row) => `${row.nombre} ${row.apellido}` },
    { key: 'carrera', label: 'Carrera', render: (row) => row.carrera || '-', exportValue: (row) => row.carrera || '-' },
    { key: 'grado', label: 'Grado', render: (row) => row.grado || '-', exportValue: (row) => row.grado || '-' },
    { key: 'seccion', label: 'Sección', render: (row) => row.seccion || '-', exportValue: (row) => row.seccion || '-' },
    { key: 'cursos', label: 'Cursos asignados', render: (row) => row.cursos?.length ? row.cursos.join(', ') : '-', exportValue: (row) => row.cursos?.length ? row.cursos.join(', ') : '-' },
    { key: 'fecha_inscripcion', label: 'Fecha de inscripción', render: (row) => row.fecha_inscripcion ? new Date(row.fecha_inscripcion).toLocaleDateString('es-GT') : '-', exportValue: (row) => row.fecha_inscripcion ? new Date(row.fecha_inscripcion).toLocaleDateString('es-GT') : '-' },
  ]
  const handleExport = async () => {
    const r = await api.get('/v1/inscripciones/resumen-alumnos', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }

  const rowActions = [
    {
      label: 'Editar',
      className: 'rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10',
      onClick: (row) => setEditAlumno({
        id_alumno: row.id_alumno,
        nombre: row.nombre,
        apellido: row.apellido,
        id_carrera: row.id_carrera ?? '',
        id_grado_actual: row.id_grado_actual ?? '',
        id_seccion_actual: row.id_seccion_actual ?? '',
      }),
    },
    {
      label: 'Gestionar',
      className: 'rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10',
      onClick: (row) => setGestionAlumno(row),
    },
  ]

  return (
    <>
      <DataTable title="Inscripciones" columns={columns} data={data} loading={loading}
        onAdd={() => navigate('/inscripciones/nuevo')}
        rowActions={rowActions}
        pagination={pagination}
        onPageChange={setPage}
        onSearch={(s) => { setQ(s); setPage(1) }}
        onExport={handleExport} />

      <Modal
        open={!!editAlumno}
        onClose={() => setEditAlumno(null)}
        title={editAlumno ? `Editar a ${editAlumno.nombre} ${editAlumno.apellido}` : ''}
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setEditAlumno(null)} className={btn.ghost}>Cancelar</button>
            <button type="submit" form="form-editar-alumno" disabled={savingEdit} className={`${btn.primary} disabled:cursor-not-allowed disabled:opacity-60`}>
              {savingEdit ? 'Guardando...' : 'Guardar'}
            </button>
          </>
        }
      >
        {editAlumno && (
          <form id="form-editar-alumno" onSubmit={guardarEdicion} className="space-y-4">
            <SearchableSelect
              label="Carrera"
              name="id_carrera"
              value={editAlumno.id_carrera}
              onChange={(e) => setEditAlumno({ ...editAlumno, id_carrera: e.target.value })}
              options={carreras}
              placeholder="Buscar carrera..."
            />
            <SearchableSelect
              label="Grado"
              name="id_grado_actual"
              value={editAlumno.id_grado_actual}
              onChange={(e) => setEditAlumno({ ...editAlumno, id_grado_actual: e.target.value })}
              options={grados}
              placeholder="Buscar grado..."
            />
            <SearchableSelect
              label="Sección"
              name="id_seccion_actual"
              value={editAlumno.id_seccion_actual}
              onChange={(e) => setEditAlumno({ ...editAlumno, id_seccion_actual: e.target.value })}
              options={secciones}
              placeholder="Buscar sección..."
            />
            <p className="text-xs text-neutral-400">
              Esto solo actualiza el grado/sección/carrera actuales del alumno — no mueve sus cursos ya inscritos. Para
              inscribirlo en los cursos del nuevo grado, usá &quot;Nuevo Registro&quot; → Por grado.
            </p>
          </form>
        )}
      </Modal>

      <Modal
        open={!!gestionAlumno}
        onClose={() => setGestionAlumno(null)}
        title={gestionAlumno ? `Cursos de ${gestionAlumno.nombre} ${gestionAlumno.apellido}` : ''}
        size="md"
        footer={
          <button type="button" onClick={() => setGestionAlumno(null)} className={btn.neutral}>Cerrar</button>
        }
      >
        {gestionAlumno?.inscripciones?.length ? (
          <div className="space-y-2">
            {gestionAlumno.inscripciones.map((ins) => (
              <div key={ins.id_inscripcion} className="flex items-center justify-between gap-3 rounded-lg border border-neutral-100 bg-neutral-50 px-4 py-2.5 dark:border-neutral-700 dark:bg-neutral-800/40">
                <div>
                  <p className="text-sm font-bold text-neutral-800 dark:text-neutral-100">{ins.curso}</p>
                  <p className="text-xs text-neutral-500 dark:text-neutral-400">
                    Inscrito el {ins.fecha_inscripcion ? new Date(ins.fecha_inscripcion).toLocaleDateString('es-GT') : '-'}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setConfirmarEliminar(ins)}
                  className="shrink-0 rounded-lg bg-danger-50 px-3 py-1.5 text-xs font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
                >
                  Eliminar
                </button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Este alumno ya no tiene inscripciones activas.</p>
        )}
      </Modal>

      <Modal
        open={!!confirmarEliminar}
        onClose={() => setConfirmarEliminar(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmarEliminar(null)} className={btn.ghost}>Cancelar</button>
            <button type="button" onClick={() => eliminarInscripcion(confirmarEliminar.id_inscripcion)} className={btn.danger}>Sí, eliminar</button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          ¿Eliminar la inscripción de {confirmarEliminar ? `"${confirmarEliminar.curso}"` : ''}? Esta acción no se puede deshacer.
        </p>
      </Modal>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>Aceptar</button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </>
  )
}
