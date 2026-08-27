import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import DataTable from '../../components/DataTable'
import Modal from '../../components/Modal'
import api from '../../api/axios'
import { btn } from '../../lib/twClasses'

const columns = [
  { key: 'id_permiso', label: 'ID' },
  { key: 'nombre', label: 'Nombre' },
  { key: 'descripcion', label: 'Descripción' },
]

export default function PermisoList() {
  const navigate = useNavigate()
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [seedMsg, setSeedMsg] = useState(null)
  const [seedLoading, setSeedLoading] = useState(false)

  const fetchData = () => {
    setLoading(true)
    api.get('/v1/permisos').then((r) => setData(r.data)).catch(console.error).finally(() => setLoading(false))
  }

  useEffect(() => { fetchData() }, [])

  const handleSeedDefaults = async () => {
    setSeedLoading(true)
    try {
      const r = await api.post('/v1/permisos/seed')
      setData(r.data.data)
      setSeedMsg({ type: 'success', text: r.data.message })
    } catch (err) {
      setSeedMsg({ type: 'error', text: err.response?.data?.message || 'Error al cargar los permisos por defecto.' })
    } finally {
      setSeedLoading(false)
    }
  }

  const handleDelete = async (permiso) => {
    try {
      await api.delete(`/v1/permisos/${permiso.id_permiso}`)
      fetchData()
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  return (
    <div className="max-w-4xl mx-auto">
      <DataTable
        title="Permisos"
        subtitle="Gestiona los permisos del sistema o carga los permisos por defecto."
        columns={columns}
        data={data}
        loading={loading}
        onAdd={() => navigate('/permisos/nuevo')}
        onEdit={(row) => navigate(`/permisos/${row.id_permiso}`)}
        onDelete={handleDelete}
        headerExtra={
          <>
            <button
              type="button"
              onClick={() => navigate('/permisos/usuarios')}
              className={btn.outline}
            >
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Permisos por Usuario
            </button>
            <button
              type="button"
              onClick={handleSeedDefaults}
              disabled={seedLoading}
              className={btn.secondary}
            >
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {seedLoading ? 'Cargando...' : 'Cargar por defecto'}
            </button>
          </>
        }
      />

      <Modal
        open={!!seedMsg}
        onClose={() => setSeedMsg(null)}
        title="Sistema"
        size="sm"
        footer={
          <button className={`${btn.primary} w-full`} onClick={() => setSeedMsg(null)}>
            Aceptar
          </button>
        }
      >
        <p className={`text-sm whitespace-pre-line ${seedMsg?.type === 'error' ? 'text-danger' : 'text-neutral-600 dark:text-neutral-300'}`}>
          {seedMsg?.text}
        </p>
      </Modal>
    </div>
  )
}
