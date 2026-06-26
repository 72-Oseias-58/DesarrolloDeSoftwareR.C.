<template>
  <main class="admin-dashboard q-pa-lg" :class="{ 'admin-dashboard-dark': q.dark.isActive }">
    <div class="admin-hero q-pa-lg q-mb-lg">
      <div>
        <div class="text-caption text-weight-bold text-uppercase">
          Panel administrativo
        </div>

        <h3 class="q-ma-none">
          Dashboard ADMIN
        </h3>

        <p class="q-mt-sm q-mb-none">
          Ventas, métricas y operación diaria de tu sucursal asignada.
        </p>
      </div>

      <q-icon name="admin_panel_settings" size="76px" class="admin-hero-icon" />
    </div>

    <q-card flat bordered class="admin-filter-card q-pa-md q-mb-lg">
      <div class="row items-center q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-select
            v-model="periodo"
            :options="periodos"
            label="Periodo"
            outlined
            dense
            emit-value
            map-options
            @update:model-value="cargarEstadisticas"
          />
        </div>

        <div v-if="periodo === 'personalizado'" class="col-12 col-md-3">
          <q-input
            v-model="fechaDesde"
            label="Desde"
            type="date"
            outlined
            dense
          />
        </div>

        <div v-if="periodo === 'personalizado'" class="col-12 col-md-3">
          <q-input
            v-model="fechaHasta"
            label="Hasta"
            type="date"
            outlined
            dense
          />
        </div>

        <div class="col-12 col-md-2">
          <q-btn
            color="primary"
            icon="search"
            label="Consultar"
            unelevated
            class="full-width"
            :loading="loadingEstadisticas"
            @click="cargarEstadisticas"
          />
        </div>
      </div>
    </q-card>

    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6 col-lg-3">
        <q-card flat bordered class="metric-card">
          <q-card-section>
            <q-icon name="payments" color="green" size="34px" />
            <div class="metric-label">Ventas</div>
            <div class="metric-value">Bs {{ formatoDinero(metricas.total_ventas) }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <q-card flat bordered class="metric-card">
          <q-card-section>
            <q-icon name="receipt_long" color="primary" size="34px" />
            <div class="metric-label">Pedidos pagados</div>
            <div class="metric-value">{{ metricas.cantidad_pedidos }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <q-card flat bordered class="metric-card">
          <q-card-section>
            <q-icon name="trending_up" color="orange" size="34px" />
            <div class="metric-label">Ticket promedio</div>
            <div class="metric-value">Bs {{ formatoDinero(metricas.ticket_promedio) }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <q-card flat bordered class="metric-card">
          <q-card-section>
            <q-icon name="qr_code_2" color="purple" size="34px" />
            <div class="metric-label">QR / Efectivo</div>
            <div class="metric-value">
              Bs {{ formatoDinero(metricas.total_qr) }} / Bs {{ formatoDinero(metricas.total_efectivo) }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="chart-card q-mb-lg">
      <q-card-section>
        <div class="row items-center justify-between q-mb-md">
          <div>
            <div class="text-h6 text-weight-bold">Ventas de mi sucursal</div>
            <div class="text-grey-7">
              Datos calculados desde pedidos pagados y no anulados.
            </div>
          </div>

          <q-btn
            flat
            round
            color="primary"
            icon="refresh"
            :loading="loadingEstadisticas"
            @click="cargarEstadisticas"
          >
            <q-tooltip>Actualizar estadísticas</q-tooltip>
          </q-btn>
        </div>

        <div v-if="loadingEstadisticas" class="chart-loading">
          <q-spinner-dots color="primary" size="48px" />
          <div class="text-grey-7 q-mt-sm">Cargando estadísticas...</div>
        </div>

        <div v-else-if="sinVentas" class="empty-chart">
          <q-icon name="query_stats" size="64px" color="grey-5" />
          <div class="text-h6 q-mt-md">Sin ventas registradas</div>
          <div class="text-grey-7">
            Cuando existan pagos válidos, el gráfico mostrará las ventas de esta sucursal.
          </div>
        </div>

        <apex-chart
          v-else
          type="area"
          height="360"
          :options="chartOptions"
          :series="chartSeries"
        />
      </q-card-section>
    </q-card>

    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">Gestión de la sucursal</div>
        <div class="text-grey-7">
          Accesos rápidos según los permisos asignados a tu usuario.
        </div>
      </div>
    </div>

    <div class="row q-col-gutter-lg">
      <div
        v-for="modulo in modulosFiltrados"
        :key="modulo.label"
        class="col-12 col-sm-6 col-lg-4"
      >
        <q-card class="module-card">
          <q-card-section>
            <q-icon :name="modulo.icon" size="42px" :color="modulo.color" />
            
            <div class="text-h6 text-weight-bold q-mt-md">
              {{ modulo.label }}
            </div>

            <div class="text-grey-7 q-mt-sm">
              {{ modulo.descripcion }}
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn
              flat
              :color="modulo.color"
              label="Entrar"
              icon-right="arrow_forward"
              @click="irA(modulo.to)"
            />
          </q-card-actions> 
        </q-card>
      </div>
    </div>
  </main>
</template>

<script src="./AdminDashboard.js"></script>

<style src="./AdminDashboard.css" scoped></style>