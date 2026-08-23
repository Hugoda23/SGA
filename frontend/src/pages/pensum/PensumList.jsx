import { useState, useEffect, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, input, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

function groupLabel(grado, carrera) {
  const gradoNombre = grado?.nombre?.trim()
  const carreraNombre = carrera?.nombre_carrera?.trim()
  if (gradoNombre && carreraNombre) return `${gradoNombre} ${carreraNombre}`
  return gradoNombre || carreraNombre || 'Sin especificar'
}

export default function PensumList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [confirmGroup, setConfirmGroup] = useState(null)
  const [deleteError, setDeleteError] = useState(null)
  const navigate = useNavigate()

  const fetchData = async () => {
    try {
      const r = await api.get('/v1/pensums')
      setData(r.data)
    } catch (e) {
      console.error(e)
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { fetchData() }, [])

  const handleDelete = async (group) => {
    for (const item of group.items) {
      await api.delete(`/v1/pensums/${item.id_pensum}`)
    }
    fetchData()
  }

  const groups = useMemo(() => {
    const map = new Map()
    for (const row of data) {
      const key = `${row.id_carrera ?? ''}|${row.id_grado ?? ''}`
      if (!map.has(key)) {
        map.set(key, {
          key,
          id_carrera: row.id_carrera,
          id_grado: row.id_grado,
          carrera: row.carrera,
          grado: row.grado,
          items: [],
        })
      }
      map.get(key).items.push(row)
    }

    const q = search.trim().toLowerCase()
    const sorted = [...map.values()].sort((a, b) => {
      const ca = a.carrera?.nombre_carrera || ''
      const cb = b.carrera?.nombre_carrera || ''
      if (ca !== cb) return ca.localeCompare(cb)
      const ga = a.grado?.nombre || 'zzzz'
      const gb = b.grado?.nombre || 'zzzz'
      return ga.localeCompare(gb)
    })

    return sorted
      .map((g) => {
        const label = groupLabel(g.grado, g.carrera).toLowerCase()
        const headerMatches = !q || label.includes(q)
        const cursos = g.items
          .filter((item) => !q || headerMatches || String(item.curso?.nombre_curso ?? '').toLowerCase().includes(q))
          .sort((a, b) => String(a.curso?.nombre_curso || '').localeCompare(String(b.curso?.nombre_curso || '')))
        return { ...g, cursos }
      })
      .filter((g) => g.cursos.length > 0)
  }, [data, search])

  const totalCursos = groups.reduce((acc, g) => acc + g.cursos.length, 0)

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Pensum</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Carreras con todos los cursos asignados por grado.</p>
        </div>
        <button type="button" onClick={() => navigate('/pensum/nuevo')} className={btn.primary}>
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Nuevo Registro
        </button>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por carrera, grado o curso..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
          <span className="text-xs font-semibold text-neutral-400">
            {groups.length} grupo(s) · {totalCursos} curso(s)
          </span>
        </div>

        {loading ? (
          <div className="flex flex-col items-center gap-3 py-16">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
            <span className="font-semibold text-neutral-500">Cargando datos...</span>
          </div>
        ) : groups.length === 0 ? (
          <div className="flex flex-col items-center justify-center px-4 py-16">
            <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay registros</span>
            <p className="text-sm text-neutral-400">Realiza una búsqueda diferente o crea un nuevo registro.</p>
          </div>
        ) : (
          <div className="divide-y divide-neutral-100 dark:divide-neutral-700">
            {groups.map((group) => (
              <div key={group.key} className="px-6 py-5">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                  <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-lg font-bold text-primary dark:text-primary-300">
                      {groupLabel(group.grado, group.carrera)}
                    </h2>
                    <span className={badge.primary}>{group.cursos.length} curso(s)</span>
                  </div>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => navigate(`/pensum/${group.items[0].id_pensum}`)}
                      className="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-warning transition-colors hover:bg-warning hover:text-white dark:bg-amber-100/10"
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      onClick={() => setConfirmGroup(group)}
                      className="rounded-lg bg-danger-50 px-3 py-1.5 text-xs font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
                    >
                      Eliminar
                    </button>
                  </div>
                </div>
                <ul className="space-y-2">
                  {group.cursos.map((item) => (
                    <li
                      key={item.id_pensum}
                      className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-neutral-50 px-4 py-2.5 dark:bg-neutral-700/40"
                    >
                      <div className="flex items-center gap-3">
                        <svg className="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span className="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                          {item.curso?.nombre_curso || 'Curso'}
                        </span>
                        {item.obligatorio ? (
                          <span className={badge.success}>Obligatorio</span>
                        ) : (
                          <span className={badge.neutral}>Opcional</span>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        )}
      </div>

      <Modal
        open={!!confirmGroup}
        onClose={() => setConfirmGroup(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmGroup(null)} className={btn.ghost}>
              Cancelar
            </button>
            <button type="button" onClick={async () => { try { await handleDelete(confirmGroup) } catch (e) { setDeleteError(e.response?.data?.message || 'No se pudo eliminar el registro porque tiene registros asociados.') } }} className={btn.danger}>
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          ¿Estás seguro de que deseas eliminar el grado "{groupLabel(confirmGroup?.grado, confirmGroup?.carrera)}" con sus {confirmGroup?.items?.length} curso(s)? Esta acción no se puede deshacer.
        </p>
      </Modal>

      <Modal
        open={!!deleteError}
        onClose={() => setDeleteError(null)}
        title="Mensaje del Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setDeleteError(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{deleteError}</p>
      </Modal>
    </div>
  )
}
