import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function CarreraList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const navigate = useNavigate()

  const fetchData = async () => {
    try { const res = await api.get('/v1/carreras'); setData(res.data) } catch (err) { console.error(err) } finally { setLoading(false) }
  }

  useEffect(() => { fetchData() }, [])

  const handleDelete = async (row) => {
    try { await api.delete(`/v1/carreras/${row.id_carrera}`); fetchData() } catch (err) { console.error(err); throw err }
  }

  const columns = [
    { key: 'nombre_carrera', label: 'Carrera' },
    { key: 'descripcion', label: 'Descripción' },
  ]

  return (
    <DataTable title="Carreras" columns={columns} data={data} loading={loading}
      onAdd={() => navigate('/carreras/nuevo')}
      onEdit={(row) => navigate(`/carreras/${row.id_carrera}`)}
      onDelete={handleDelete} />
  )
}
