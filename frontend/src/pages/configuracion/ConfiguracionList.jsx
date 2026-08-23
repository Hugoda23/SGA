import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import DataTable from '../../components/DataTable'
import api from '../../api/axios'

const columns = [
  { key: 'id_config', label: 'ID' },
  { key: 'clave', label: 'Clave' },
  { key: 'valor', label: 'Valor' },
]

export default function ConfiguracionList() {
  const navigate = useNavigate()
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)

  const fetchData = () => {
    setLoading(true)
    api.get('/v1/configuraciones').then((r) => setData(r.data)).catch(console.error).finally(() => setLoading(false))
  }

  useEffect(() => { fetchData() }, [])

  const handleDelete = async (item) => {
    try {
      await api.delete(`/v1/configuraciones/${item.id_config}`)
      fetchData()
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  return (
    <div className="max-w-4xl mx-auto">
      <DataTable
        title="Configuración del Sistema"
        columns={columns}
        data={data}
        loading={loading}
        onAdd={() => navigate('/configuracion/nuevo')}
        onEdit={(row) => navigate(`/configuracion/${row.id_config}`)}
        onDelete={handleDelete}
      />
    </div>
  )
}
