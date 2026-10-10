import { Suspense } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute'
import Layout from './components/Layout'
import { lazyConRecarga } from './lib/lazyConRecarga'

const Login = lazyConRecarga(() => import('./pages/Login'))
const Mantenimiento = lazyConRecarga(() => import('./pages/Mantenimiento'))
const ChangePassword = lazyConRecarga(() => import('./pages/ChangePassword'))
const Dashboard = lazyConRecarga(() => import('./pages/Dashboard'))

const AlumnoList = lazyConRecarga(() => import('./pages/alumnos/AlumnoList'))
const AlumnoForm = lazyConRecarga(() => import('./pages/alumnos/AlumnoForm'))

const CatedraticoList = lazyConRecarga(() => import('./pages/catedraticos/CatedraticoList'))
const CatedraticoForm = lazyConRecarga(() => import('./pages/catedraticos/CatedraticoForm'))
const MisCursos = lazyConRecarga(() => import('./pages/catedraticos/MisCursos'))
const RegistroCalificaciones = lazyConRecarga(() => import('./pages/catedraticos/RegistroCalificaciones'))
const RegistroCalificacionesIndex = lazyConRecarga(() => import('./pages/catedraticos/RegistroCalificacionesIndex'))
const ConfiguracionCursoIndex = lazyConRecarga(() => import('./pages/catedraticos/ConfiguracionCursoIndex'))
const ConfiguracionCurso = lazyConRecarga(() => import('./pages/catedraticos/ConfiguracionCurso'))
const MisCursosAlumno = lazyConRecarga(() => import('./pages/alumno/MisCursosAlumno'))
const MiHorario = lazyConRecarga(() => import('./pages/alumno/MiHorario'))
const CursoAlumno = lazyConRecarga(() => import('./pages/alumno/CursoAlumno'))
const AsistenciaIndex = lazyConRecarga(() => import('./pages/asistencias/AsistenciaIndex'))
const AsistenciaCurso = lazyConRecarga(() => import('./pages/asistencias/AsistenciaCurso'))

const CursoList = lazyConRecarga(() => import('./pages/cursos/CursoList'))
const CursoForm = lazyConRecarga(() => import('./pages/cursos/CursoForm'))

const CarreraList = lazyConRecarga(() => import('./pages/carreras/CarreraList'))
const CarreraForm = lazyConRecarga(() => import('./pages/carreras/CarreraForm'))

const EdificioList = lazyConRecarga(() => import('./pages/edificios/EdificioList'))
const EdificioForm = lazyConRecarga(() => import('./pages/edificios/EdificioForm'))

const AulaList = lazyConRecarga(() => import('./pages/aulas/AulaList'))
const AulaForm = lazyConRecarga(() => import('./pages/aulas/AulaForm'))

const GradoList = lazyConRecarga(() => import('./pages/grados/GradoList'))
const SeccionList = lazyConRecarga(() => import('./pages/secciones/SeccionList'))

const PeriodoList = lazyConRecarga(() => import('./pages/periodos/PeriodoList'))
const PeriodoForm = lazyConRecarga(() => import('./pages/periodos/PeriodoForm'))

const PensumList = lazyConRecarga(() => import('./pages/pensum/PensumList'))
const PensumForm = lazyConRecarga(() => import('./pages/pensum/PensumForm'))

const AsignacionList = lazyConRecarga(() => import('./pages/asignaciones/AsignacionList'))
const AsignacionForm = lazyConRecarga(() => import('./pages/asignaciones/AsignacionForm'))

const InscripcionList = lazyConRecarga(() => import('./pages/inscripciones/InscripcionList'))
const InscripcionForm = lazyConRecarga(() => import('./pages/inscripciones/InscripcionForm'))

const TareaList = lazyConRecarga(() => import('./pages/tareas/TareaList'))
const TareaForm = lazyConRecarga(() => import('./pages/tareas/TareaForm'))
const MisTareas = lazyConRecarga(() => import('./pages/tareas/MisTareas'))
const EntregasList = lazyConRecarga(() => import('./pages/entregas/EntregasList'))

const EvaluacionList = lazyConRecarga(() => import('./pages/evaluaciones/EvaluacionList'))
const EvaluacionForm = lazyConRecarga(() => import('./pages/evaluaciones/EvaluacionForm'))

const UserList = lazyConRecarga(() => import('./pages/admin/UserList'))
const AuditoriaList = lazyConRecarga(() => import('./pages/admin/AuditoriaList'))
const LogList = lazyConRecarga(() => import('./pages/admin/LogList'))

const ReporteActas = lazyConRecarga(() => import('./pages/reportes/ReporteActas'))
const ReporteListadoAlumnos = lazyConRecarga(() => import('./pages/reportes/ReporteListadoAlumnos'))
const ReporteNotas = lazyConRecarga(() => import('./pages/reportes/ReporteNotas'))
const ReporteConstancias = lazyConRecarga(() => import('./pages/reportes/ReporteConstancias'))
const ReporteRendimiento = lazyConRecarga(() => import('./pages/reportes/ReporteRendimiento'))

const RolList = lazyConRecarga(() => import('./pages/roles/RolList'))
const RolForm = lazyConRecarga(() => import('./pages/roles/RolForm'))
const PermisoList = lazyConRecarga(() => import('./pages/permisos/PermisoList'))
const PermisoForm = lazyConRecarga(() => import('./pages/permisos/PermisoForm'))
const PermisoUsuario = lazyConRecarga(() => import('./pages/permisos/PermisoUsuario'))
const ConfiguracionForm = lazyConRecarga(() => import('./pages/configuracion/ConfiguracionForm'))
const NotificacionList = lazyConRecarga(() => import('./pages/notificaciones/NotificacionList'))
const ReporteGeneradoList = lazyConRecarga(() => import('./pages/reportes/ReporteGeneradoList'))

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
        <Route path="/mantenimiento" element={<Mantenimiento />} />
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
            <RouteGuard roles={['alumno', 'catedratico']}>
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
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <CarreraList />
            </RouteGuard>
          } />
          <Route path="carreras/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <CarreraForm />
            </RouteGuard>
          } />
          <Route path="carreras/:id" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
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
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <PeriodoList />
            </RouteGuard>
          } />
          <Route path="periodos/nuevo" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <PeriodoForm />
            </RouteGuard>
          } />
          <Route path="periodos/:id" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
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
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <ReporteActas />
            </RouteGuard>
          } />
          <Route path="reportes/listado-alumnos" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <ReporteListadoAlumnos />
            </RouteGuard>
          } />
          <Route path="reportes/notas" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <ReporteNotas />
            </RouteGuard>
          } />
          <Route path="reportes/constancias" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
              <ReporteConstancias />
            </RouteGuard>
          } />
          <Route path="reportes/rendimiento" element={
            <RouteGuard roles={['admin', 'director', 'secretaria']}>
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
          <Route path="permisos/usuarios" element={
            <ProtectedRoute roles={['admin']}>
              <PermisoUsuario />
            </ProtectedRoute>
          } />

          <Route path="configuracion" element={
            <ProtectedRoute roles={['admin']}>
              <ConfiguracionForm />
            </ProtectedRoute>
          } />

          <Route path="notificaciones" element={<NotificacionList />} />
          <Route path="reportes-generados" element={
            <ProtectedRoute roles={['admin', 'director', 'secretaria']}>
              <ReporteGeneradoList />
            </ProtectedRoute>
          } />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Suspense>
  )
}
