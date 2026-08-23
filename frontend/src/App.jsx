import { lazy, Suspense } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute'
import Layout from './components/Layout'

const Login = lazy(() => import('./pages/Login'))
const ChangePassword = lazy(() => import('./pages/ChangePassword'))
const Dashboard = lazy(() => import('./pages/Dashboard'))

const AlumnoList = lazy(() => import('./pages/alumnos/AlumnoList'))
const AlumnoForm = lazy(() => import('./pages/alumnos/AlumnoForm'))

const CatedraticoList = lazy(() => import('./pages/catedraticos/CatedraticoList'))
const CatedraticoForm = lazy(() => import('./pages/catedraticos/CatedraticoForm'))
const MisCursos = lazy(() => import('./pages/catedraticos/MisCursos'))
const RegistroCalificaciones = lazy(() => import('./pages/catedraticos/RegistroCalificaciones'))
const RegistroCalificacionesIndex = lazy(() => import('./pages/catedraticos/RegistroCalificacionesIndex'))
const ConfiguracionCursoIndex = lazy(() => import('./pages/catedraticos/ConfiguracionCursoIndex'))
const ConfiguracionCurso = lazy(() => import('./pages/catedraticos/ConfiguracionCurso'))
const MisCursosAlumno = lazy(() => import('./pages/alumno/MisCursosAlumno'))
const MiHorario = lazy(() => import('./pages/alumno/MiHorario'))
const CursoAlumno = lazy(() => import('./pages/alumno/CursoAlumno'))
const AsistenciaIndex = lazy(() => import('./pages/asistencias/AsistenciaIndex'))
const AsistenciaCurso = lazy(() => import('./pages/asistencias/AsistenciaCurso'))

const CursoList = lazy(() => import('./pages/cursos/CursoList'))
const CursoForm = lazy(() => import('./pages/cursos/CursoForm'))

const CarreraList = lazy(() => import('./pages/carreras/CarreraList'))
const CarreraForm = lazy(() => import('./pages/carreras/CarreraForm'))

const EdificioList = lazy(() => import('./pages/edificios/EdificioList'))
const EdificioForm = lazy(() => import('./pages/edificios/EdificioForm'))

const AulaList = lazy(() => import('./pages/aulas/AulaList'))
const AulaForm = lazy(() => import('./pages/aulas/AulaForm'))

const GradoList = lazy(() => import('./pages/grados/GradoList'))
const SeccionList = lazy(() => import('./pages/secciones/SeccionList'))

const PeriodoList = lazy(() => import('./pages/periodos/PeriodoList'))
const PeriodoForm = lazy(() => import('./pages/periodos/PeriodoForm'))

const PensumList = lazy(() => import('./pages/pensum/PensumList'))
const PensumForm = lazy(() => import('./pages/pensum/PensumForm'))

const AsignacionList = lazy(() => import('./pages/asignaciones/AsignacionList'))
const AsignacionForm = lazy(() => import('./pages/asignaciones/AsignacionForm'))

const InscripcionList = lazy(() => import('./pages/inscripciones/InscripcionList'))
const InscripcionForm = lazy(() => import('./pages/inscripciones/InscripcionForm'))

const TareaList = lazy(() => import('./pages/tareas/TareaList'))
const TareaForm = lazy(() => import('./pages/tareas/TareaForm'))
const MisTareas = lazy(() => import('./pages/tareas/MisTareas'))
const EntregasList = lazy(() => import('./pages/entregas/EntregasList'))

const EvaluacionList = lazy(() => import('./pages/evaluaciones/EvaluacionList'))
const EvaluacionForm = lazy(() => import('./pages/evaluaciones/EvaluacionForm'))

const UserList = lazy(() => import('./pages/admin/UserList'))
const AuditoriaList = lazy(() => import('./pages/admin/AuditoriaList'))
const LogList = lazy(() => import('./pages/admin/LogList'))

const ReporteActas = lazy(() => import('./pages/reportes/ReporteActas'))
const ReporteNotas = lazy(() => import('./pages/reportes/ReporteNotas'))
const ReporteConstancias = lazy(() => import('./pages/reportes/ReporteConstancias'))
const ReporteRendimiento = lazy(() => import('./pages/reportes/ReporteRendimiento'))

const RolList = lazy(() => import('./pages/roles/RolList'))
const RolForm = lazy(() => import('./pages/roles/RolForm'))
const PermisoList = lazy(() => import('./pages/permisos/PermisoList'))
const PermisoForm = lazy(() => import('./pages/permisos/PermisoForm'))
const ConfiguracionList = lazy(() => import('./pages/configuracion/ConfiguracionList'))
const ConfiguracionForm = lazy(() => import('./pages/configuracion/ConfiguracionForm'))
const NotificacionList = lazy(() => import('./pages/notificaciones/NotificacionList'))
const ReporteGeneradoList = lazy(() => import('./pages/reportes/ReporteGeneradoList'))

function PageLoader() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-neutral-100 dark:bg-surface-dark">
      <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
    </div>
  )
}

function RouteGuard({ roles, permisos, children }) {
  return (
    <ProtectedRoute roles={roles} permisos={permisos}>
      {children}
    </ProtectedRoute>
  )
}

export default function App() {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/cambiar-contrasena" element={
          <ProtectedRoute>
            <ChangePassword />
          </ProtectedRoute>
        } />

        <Route
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Dashboard />} />

          <Route path="alumnos" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AlumnoList />
            </RouteGuard>
          } />
          <Route path="alumnos/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AlumnoForm />
            </RouteGuard>
          } />
          <Route path="alumnos/:id" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AlumnoForm />
            </RouteGuard>
          } />

          <Route path="mis-cursos" element={
            <RouteGuard roles={['catedratico']}>
              <MisCursos />
            </RouteGuard>
          } />
          <Route path="asistencia" element={
            <RouteGuard roles={['catedratico']}>
              <AsistenciaIndex />
            </RouteGuard>
          } />
          <Route path="asistencia/:id_asignacion" element={
            <RouteGuard roles={['catedratico']}>
              <AsistenciaCurso />
            </RouteGuard>
          } />
          <Route path="registro-calificaciones" element={
            <RouteGuard roles={['catedratico']}>
              <RegistroCalificacionesIndex />
            </RouteGuard>
          } />
          <Route path="registro-calificaciones/:id_asignacion" element={
            <RouteGuard roles={['catedratico']}>
              <RegistroCalificaciones />
            </RouteGuard>
          } />
          <Route path="configuracion-curso" element={
            <RouteGuard roles={['catedratico']}>
              <ConfiguracionCursoIndex />
            </RouteGuard>
          } />
          <Route path="configuracion-curso/:id_asignacion" element={
            <RouteGuard roles={['catedratico']}>
              <ConfiguracionCurso />
            </RouteGuard>
          } />

          <Route path="mis-cursos-alumno" element={
            <RouteGuard roles={['alumno']}>
              <MisCursosAlumno />
            </RouteGuard>
          } />
          <Route path="mis-cursos-alumno/:id_asignacion" element={
            <RouteGuard roles={['alumno']}>
              <CursoAlumno />
            </RouteGuard>
          } />
          <Route path="mi-horario" element={
            <RouteGuard roles={['alumno']}>
              <MiHorario />
            </RouteGuard>
          } />

          <Route path="catedraticos" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <CatedraticoList />
            </RouteGuard>
          } />
          <Route path="catedraticos/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <CatedraticoForm />
            </RouteGuard>
          } />
          <Route path="catedraticos/:id" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <CatedraticoForm />
            </RouteGuard>
          } />

          <Route path="cursos" element={
            <RouteGuard roles={['admin', 'director']}>
              <CursoList />
            </RouteGuard>
          } />
          <Route path="cursos/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <CursoForm />
            </RouteGuard>
          } />
          <Route path="cursos/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <CursoForm />
            </RouteGuard>
          } />

          <Route path="carreras" element={
            <RouteGuard roles={['admin', 'director']}>
              <CarreraList />
            </RouteGuard>
          } />
          <Route path="carreras/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <CarreraForm />
            </RouteGuard>
          } />
          <Route path="carreras/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <CarreraForm />
            </RouteGuard>
          } />

          <Route path="edificios" element={
            <RouteGuard roles={['admin', 'director']}>
              <EdificioList />
            </RouteGuard>
          } />
          <Route path="edificios/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <EdificioForm />
            </RouteGuard>
          } />
          <Route path="edificios/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <EdificioForm />
            </RouteGuard>
          } />

          <Route path="aulas" element={
            <RouteGuard roles={['admin', 'director']}>
              <AulaList />
            </RouteGuard>
          } />
          <Route path="aulas/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <AulaForm />
            </RouteGuard>
          } />
          <Route path="aulas/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <AulaForm />
            </RouteGuard>
          } />

          <Route path="grados" element={
            <RouteGuard roles={['admin', 'director']}>
              <GradoList />
            </RouteGuard>
          } />
          <Route path="secciones" element={
            <RouteGuard roles={['admin', 'director']}>
              <SeccionList />
            </RouteGuard>
          } />

          <Route path="periodos" element={
            <RouteGuard roles={['admin', 'director']}>
              <PeriodoList />
            </RouteGuard>
          } />
          <Route path="periodos/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <PeriodoForm />
            </RouteGuard>
          } />
          <Route path="periodos/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <PeriodoForm />
            </RouteGuard>
          } />

          <Route path="pensum" element={
            <RouteGuard roles={['admin', 'director']}>
              <PensumList />
            </RouteGuard>
          } />
          <Route path="pensum/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <PensumForm />
            </RouteGuard>
          } />
          <Route path="pensum/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <PensumForm />
            </RouteGuard>
          } />

          <Route path="asignaciones" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AsignacionList />
            </RouteGuard>
          } />
          <Route path="asignaciones/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AsignacionForm />
            </RouteGuard>
          } />
          <Route path="asignaciones/:id" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <AsignacionForm />
            </RouteGuard>
          } />

          <Route path="inscripciones" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <InscripcionList />
            </RouteGuard>
          } />
          <Route path="inscripciones/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <InscripcionForm />
            </RouteGuard>
          } />

          <Route path="tareas" element={
            <RouteGuard roles={['admin', 'director', 'catedratico']}>
              <TareaList />
            </RouteGuard>
          } />
          <Route path="tareas/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'catedratico']}>
              <TareaForm />
            </RouteGuard>
          } />
          <Route path="tareas/:id" element={
            <RouteGuard roles={['admin', 'director', 'catedratico']}>
              <TareaForm />
            </RouteGuard>
          } />
          <Route path="mis-tareas" element={
            <RouteGuard roles={['alumno']}>
              <MisTareas />
            </RouteGuard>
          } />
          <Route path="entregas-tarea" element={
            <RouteGuard roles={['catedratico']}>
              <EntregasList />
            </RouteGuard>
          } />

          <Route path="evaluaciones" element={
            <RouteGuard roles={['admin', 'director']}>
              <EvaluacionList />
            </RouteGuard>
          } />
          <Route path="evaluaciones/nuevo" element={
            <RouteGuard roles={['admin', 'director']}>
              <EvaluacionForm />
            </RouteGuard>
          } />
          <Route path="evaluaciones/:id" element={
            <RouteGuard roles={['admin', 'director']}>
              <EvaluacionForm />
            </RouteGuard>
          } />

          <Route path="reportes/actas" element={
            <RouteGuard roles={['admin', 'director']}>
              <ReporteActas />
            </RouteGuard>
          } />
          <Route path="reportes/notas" element={
            <RouteGuard roles={['admin', 'director']}>
              <ReporteNotas />
            </RouteGuard>
          } />
          <Route path="reportes/constancias" element={
            <RouteGuard roles={['admin', 'director']}>
              <ReporteConstancias />
            </RouteGuard>
          } />
          <Route path="reportes/rendimiento" element={
            <RouteGuard roles={['admin', 'director']}>
              <ReporteRendimiento />
            </RouteGuard>
          } />

          <Route path="auditoria" element={
            <ProtectedRoute roles={['admin']}>
              <AuditoriaList />
            </ProtectedRoute>
          } />

          <Route path="admin/logs" element={
            <ProtectedRoute roles={['admin']}>
              <LogList />
            </ProtectedRoute>
          } />

          <Route path="admin/usuarios" element={
            <ProtectedRoute roles={['admin']}>
              <UserList />
            </ProtectedRoute>
          } />

          <Route path="roles" element={
            <ProtectedRoute roles={['admin']}>
              <RolList />
            </ProtectedRoute>
          } />
          <Route path="roles/nuevo" element={
            <ProtectedRoute roles={['admin']}>
              <RolForm />
            </ProtectedRoute>
          } />
          <Route path="roles/:id" element={
            <ProtectedRoute roles={['admin']}>
              <RolForm />
            </ProtectedRoute>
          } />

          <Route path="permisos" element={
            <ProtectedRoute roles={['admin']}>
              <PermisoList />
            </ProtectedRoute>
          } />
          <Route path="permisos/nuevo" element={
            <ProtectedRoute roles={['admin']}>
              <PermisoForm />
            </ProtectedRoute>
          } />
          <Route path="permisos/:id" element={
            <ProtectedRoute roles={['admin']}>
              <PermisoForm />
            </ProtectedRoute>
          } />

          <Route path="configuracion" element={
            <ProtectedRoute roles={['admin']}>
              <ConfiguracionList />
            </ProtectedRoute>
          } />
          <Route path="configuracion/nuevo" element={
            <ProtectedRoute roles={['admin']}>
              <ConfiguracionForm />
            </ProtectedRoute>
          } />
          <Route path="configuracion/:id" element={
            <ProtectedRoute roles={['admin']}>
              <ConfiguracionForm />
            </ProtectedRoute>
          } />

          <Route path="notificaciones" element={<NotificacionList />} />
          <Route path="reportes-generados" element={
            <ProtectedRoute roles={['admin', 'director']}>
              <ReporteGeneradoList />
            </ProtectedRoute>
          } />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Suspense>
  )
}
