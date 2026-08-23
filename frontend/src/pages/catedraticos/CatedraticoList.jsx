import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function CatedraticoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()

  const fetchData = useCallback(async () => {
    setLoading(true)
    try {
      const res = await api.get('/v1/catedraticos', { params: { page, q: q || undefined } })
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
      await api.delete(`/v1/catedraticos/${row.id_catedratico}`)
      fetchData()
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  const columns = [
    { key: 'codigo', label: 'Código' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'apellido', label: 'Apellido' },
    { key: 'especialidad', label: 'Especialidad' },
    { key: 'correo', label: 'Correo' },
    { key: 'telefono', label: 'Teléfono' },
  ]

  const handleExport = async () => {
    const r = await api.get('/v1/catedraticos', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }

  return (
    <DataTable
      title="Catedráticos"
      columns={columns}
      data={data}
      loading={loading}
      onAdd={() => navigate('/catedraticos/nuevo')}
      onEdit={(row) => navigate(`/catedraticos/${row.id_catedratico}`)}
      onDelete={handleDelete}
      pagination={pagination}
      onPageChange={setPage}
      onSearch={(s) => { setQ(s); setPage(1) }}
      onExport={handleExport}
    />
  )
}
