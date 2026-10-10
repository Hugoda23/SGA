import { useState, useEffect, useCallback, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'
import { btn, input } from '../../lib/twClasses'

const FILTROS_VACIOS = { genero: '', id_carrera: '', id_grado: '', id_seccion: '', estado_academico: '' }
// Con w-auto el selector de carrera, de nombres largos, se come la fila.
// Los filtros vacíos no viajan en la URL.
const soloElegidos = (filtros) => Object.fromEntries(Object.entries(filtros).filter(([, v]) => v !== ''))
const selectClase = `${input.base} lg:w-auto lg:max-w-56`

export default function AlumnoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [filtros, setFiltros] = useState(FILTROS_VACIOS)
  const [opciones, setOpciones] = useState({ carreras: [], grados: [], secciones: [] })
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()
  const ultimaPeticion = useRef(0)

  useEffect(() => {
    api.get('/v1/alumnos/filtros').then((res) => setOpciones(res.data)).catch(console.error)
  }, [])

  const hayFiltros = Object.values(filtros).some((v) => v !== '')

  const cambiarFiltro = (campo) => (e) => {
    setFiltros((f) => ({ ...f, [campo]: e.target.value }))
    setPage(1)
  }

  const fetchData = useCallback(async () => {
    // Al cambiar dos filtros seguidos, la respuesta del primero puede llegar
    // después que la del segundo y pisarla; solo vale la última petición.
    const esta = ++ultimaPeticion.current
    setLoading(true)
    try {
      const res = await api.get('/v1/alumnos', { params: { page, q: q || undefined, ...soloElegidos(filtros) } })
      if (esta !== ultimaPeticion.current) return
      setData(res.data)
      setPagination({ current_page: res.data.current_page, last_page: res.data.last_page, total: res.data.total })
    } catch (err) {
      console.error(err)
    } finally {
      if (esta === ultimaPeticion.current) setLoading(false)
    }
  }, [page, q, filtros])

  useEffect(() => { fetchData() }, [fetchData])

  const handleDelete = async (row) => {
    try {
      await api.delete(`/v1/alumnos/${row.id_alumno}`)
      fetchData()
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  const estadosColor = {
    activo: 'text-success',
    inactivo: 'text-neutral-500',
    egresado: 'text-primary',
    retirado: 'text-error',
  }

  const generosLabel = { masculino: 'Masculino', femenino: 'Femenino' }
  const tiposDocumentoLabel = { cui: 'CUI', pasaporte: 'Pasaporte' }

  const columns = [
    { key: 'codigo_mineduc', label: 'Código' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'apellido', label: 'Apellido' },
    { key: 'genero', label: 'Género', render: (row) => generosLabel[row.genero] || '-', exportValue: (row) => generosLabel[row.genero] || '-' },
    { key: 'nacionalidad', label: 'Nacionalidad', render: (row) => row.nacionalidad || '-', exportValue: (row) => row.nacionalidad || '-' },
    { key: 'tipo_documento', label: 'Documento', render: (row) => tiposDocumentoLabel[row.tipo_documento] || '-', exportValue: (row) => tiposDocumentoLabel[row.tipo_documento] || '-' },
    { key: 'numero_documento', label: 'No. Documento', render: (row) => row.numero_documento || '-', exportValue: (row) => row.numero_documento || '-' },
    { key: 'correo', label: 'Correo' },
    { key: 'telefono', label: 'Teléfono' },
    { key: 'carrera', label: 'Carrera', render: (row) => row.carrera?.nombre_carrera || '-', exportValue: (row) => row.carrera?.nombre_carrera || '-' },
    { key: 'grado', label: 'Grado', render: (row) => row.grado?.nombre || '-', exportValue: (row) => row.grado?.nombre || '-' },
    { key: 'seccion', label: 'Sección', render: (row) => row.seccion?.nombre || '-', exportValue: (row) => row.seccion?.nombre || '-' },
    {
      key: 'estado_academico',
      label: 'Estado Académico',
      render: (row) => {
        const estado = row.estado_academico || 'activo'
        const label = estado.charAt(0).toUpperCase() + estado.slice(1)
        return <span className={`font-bold ${estadosColor[estado] || 'text-neutral-500'}`}>{label}</span>
      },
      exportValue: (row) => row.estado_academico || 'activo',
    },
  ]

  const handleExport = async () => {
    const r = await api.get('/v1/alumnos', { params: { per_page: 1000, q: q || undefined, ...soloElegidos(filtros) } })
    return normList(r.data)
  }

  return (
    <DataTable
      title="Alumnos"
      columns={columns}
      data={data}
      loading={loading}
      onAdd={() => navigate('/alumnos/nuevo')}
      onEdit={(row) => navigate(`/alumnos/${row.id_alumno}`)}
      onDelete={handleDelete}
      pagination={pagination}
      onPageChange={setPage}
      onSearch={(s) => { setQ(s); setPage(1) }}
      onExport={handleExport}
      filters={
        <>
          <select value={filtros.genero} onChange={cambiarFiltro('genero')} aria-label="Filtrar por género" className={selectClase}>
            <option value="">Todos los géneros</option>
            <option value="masculino">Masculino</option>
            <option value="femenino">Femenino</option>
            <option value="sin_registrar">Sin registrar</option>
          </select>
          <select value={filtros.id_carrera} onChange={cambiarFiltro('id_carrera')} aria-label="Filtrar por carrera" className={selectClase}>
            <option value="">Todas las carreras</option>
            {opciones.carreras.map((c) => <option key={c.id_carrera} value={c.id_carrera}>{c.nombre_carrera}</option>)}
          </select>
          <select value={filtros.id_grado} onChange={cambiarFiltro('id_grado')} aria-label="Filtrar por grado" className={selectClase}>
            <option value="">Todos los grados</option>
            {opciones.grados.map((g) => <option key={g.id_grado} value={g.id_grado}>{g.nombre}</option>)}
            <option value="sin_grado">Sin grado</option>
          </select>
          <select value={filtros.id_seccion} onChange={cambiarFiltro('id_seccion')} aria-label="Filtrar por sección" className={selectClase}>
            <option value="">Todas las secciones</option>
            {opciones.secciones.map((sec) => <option key={sec.id_seccion} value={sec.id_seccion}>{sec.nombre}</option>)}
          </select>
          <select value={filtros.estado_academico} onChange={cambiarFiltro('estado_academico')} aria-label="Filtrar por estado" className={selectClase}>
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
            <option value="egresado">Egresado</option>
            <option value="retirado">Retirado</option>
          </select>
          {hayFiltros && (
            <button type="button" onClick={() => { setFiltros(FILTROS_VACIOS); setPage(1) }} className={btn.outline}>
              Limpiar filtros
            </button>
          )}
        </>
      }
    />
  )
}
