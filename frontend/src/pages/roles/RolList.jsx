import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import DataTable from '../../components/DataTable'
import api from '../../api/axios'

const columns = [
  { key: 'id_rol', label: 'ID' },
  { key: 'nombre', label: 'Nombre' },
  { key: 'descripcion', label: 'Descripción' },
]

export default function RolList() {
  const navigate = useNavigate()
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)

  const fetchData = () => {
    setLoading(true)
    api.get('/v1/roles').then((r) => setData(r.data)).catch(console.error).finally(() => setLoading(false))
  }

  useEffect(() => { fetchData() }, [])

  const handleDelete = async (rol) => {
    try {
      await api.delete(`/v1/roles/${rol.id_rol}`)
      fetchData()
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  return (
    <div className="max-w-4xl mx-auto">
      <DataTable
        title="Roles"
        columns={columns}
        data={data}
        loading={loading}
        onAdd={() => navigate('/roles/nuevo')}
        onEdit={(row) => navigate(`/roles/${row.id_rol}`)}
        onDelete={handleDelete}
      />
    </div>
  )
}
