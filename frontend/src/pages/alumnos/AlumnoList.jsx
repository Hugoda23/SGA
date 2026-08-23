import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function AlumnoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()

  const fetchData = useCallback(async () => {
    setLoading(true)
    try {
      const res = await api.get('/v1/alumnos', { params: { page, q: q || undefined } })
      setData(res.data)
      setPagination({ current_page: res.data.current_page, last_page: res.data.last_page, total: res.data.total })
    } catch (err) {
      console.error(err)
    } finally {
      setLoading(false)
    }
  }, [page, q])

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

  const columns = [
    { key: 'codigo_mineduc', label: 'Código' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'apellido', label: 'Apellido' },
    { key: 'correo', label: 'Correo' },
    { key: 'telefono', label: 'Teléfono' },
    { key: 'carrera', label: 'Carrera', render: (row) => row.carrera?.nombre_carrera || '-', exportValue: (row) => row.carrera?.nombre_carrera || '-' },
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
    const r = await api.get('/v1/alumnos', { params: { per_page: 1000, q: q || undefined } })
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
    />
  )
}
