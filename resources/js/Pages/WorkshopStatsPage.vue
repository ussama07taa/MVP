<template>
  <div class="max-w-7xl mx-auto pb-10 px-4">
    <!-- Header -->
    <header class="mb-10 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
      <div>
        <div class="flex items-center mb-1">
          <div class="bg-amber-500 p-3 rounded-2xl shadow-lg shadow-amber-200 mr-4">
            <HammerIcon class="w-7 h-7 text-white"/>
          </div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Atelier <span class="text-amber-500">Stats</span></h1>
        </div>
        <p class="text-slate-400 font-medium ml-14 flex items-center">
          <CalendarIcon class="w-4 h-4 mr-2" />
          Statistiques de l'atelier pour <span class="mx-1.5 font-black text-slate-700 uppercase">{{ data.month_name || '...' }}</span>
        </p>
      </div>

      <div class="flex items-center gap-4">
        <div class="flex items-center bg-white p-2 rounded-[22px] border border-slate-100 shadow-sm">
          <select v-model="selectedMonth" @change="loadStats" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
            <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
          </select>
          <div class="w-px h-4 bg-slate-100 mx-2"></div>
          <select v-model="selectedYear" @change="loadStats" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button @click="loadStats" :class="isLoading ? 'opacity-50 pointer-events-none' : ''" class="group p-4 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-300 active:scale-90" title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-amber-600 transition-transform duration-500" />
        </button>
      </div>
    </header>

    <!-- Loading -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-40">
      <div class="relative">
        <div class="w-20 h-20 border-4 border-slate-100 rounded-full"></div>
        <div class="w-20 h-20 border-4 border-t-amber-500 rounded-full animate-spin absolute top-0 left-0"></div>
      </div>
      <p class="mt-8 text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] animate-pulse">Chargement des stats atelier...</p>
    </div>

    <!-- Main Content -->
    <div v-else class="space-y-10">

      <!-- Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 hover:-translate-y-1 transition-all duration-300">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center"><ClipboardListIcon class="w-5 h-5 text-blue-600"/></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Jobs</span>
          </div>
          <p class="text-3xl font-black text-slate-900">{{ data.summary.total_jobs }}</p>
          <p class="text-xs font-bold text-slate-400 mt-1">Ce mois</p>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 hover:-translate-y-1 transition-all duration-300">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center"><CheckCircleIcon class="w-5 h-5 text-emerald-600"/></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Livrés</span>
          </div>
          <p class="text-3xl font-black text-emerald-600">{{ data.summary.delivered_jobs }}</p>
          <p class="text-xs font-bold text-slate-400 mt-1">{{ data.summary.delivery_rate }}% taux de livraison</p>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 hover:-translate-y-1 transition-all duration-300">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center"><ClockIcon class="w-5 h-5 text-amber-600"/></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Temps Moyen</span>
          </div>
          <p class="text-3xl font-black text-slate-900">{{ formatMinutes(data.summary.avg_completion_min) }}</p>
          <p class="text-xs font-bold text-slate-400 mt-1">Pour compléter un job</p>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 hover:-translate-y-1 transition-all duration-300">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center"><WrenchIcon class="w-5 h-5 text-purple-600"/></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Services</span>
          </div>
          <p class="text-3xl font-black text-slate-900">{{ data.summary.completed_services }}<span class="text-lg text-slate-400">/{{ data.summary.total_services }}</span></p>
          <p class="text-xs font-bold text-slate-400 mt-1">Services complétés</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Jobs per Day Chart -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
          <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
            <BarChart3Icon class="w-4 h-4 mr-2 text-amber-500"/> Jobs par Jour
          </h3>
          <div class="h-64">
            <Bar :data="jobsPerDayChartData" :options="barChartOptions" />
          </div>
        </div>

        <!-- Service Distribution Pie -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
          <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
            <PieChartIcon class="w-4 h-4 mr-2 text-amber-500"/> Services
          </h3>
          <div class="h-64 flex items-center justify-center">
            <Doughnut v-if="data.service_distribution.length" :data="serviceChartData" :options="doughnutOptions" />
            <p v-else class="text-sm text-slate-400 font-bold">Aucune donnée</p>
          </div>
        </div>
      </div>

      <!-- Worker Performance + Busiest Hours -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Worker Performance -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
          <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
            <UsersIcon class="w-4 h-4 mr-2 text-amber-500"/> Performance des Ouvriers
          </h3>
          <div v-if="data.worker_performance.length" class="space-y-4">
            <div v-for="(w, i) in data.worker_performance" :key="w.worker_id" class="flex items-center gap-4">
              <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm"
                :class="i === 0 ? 'bg-amber-100 text-amber-700' : i === 1 ? 'bg-slate-100 text-slate-600' : 'bg-slate-50 text-slate-500'">
                {{ i + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-black text-slate-800 text-sm truncate">{{ w.worker_name }}</p>
                <div class="w-full bg-slate-100 rounded-full h-2 mt-1">
                  <div class="h-2 rounded-full transition-all duration-500"
                    :class="i === 0 ? 'bg-amber-500' : i === 1 ? 'bg-blue-400' : 'bg-slate-300'"
                    :style="{ width: maxWorkerServices > 0 ? (w.services_done / maxWorkerServices * 100) + '%' : '0%' }">
                  </div>
                </div>
              </div>
              <div class="text-right">
                <p class="font-black text-slate-900 text-lg">{{ w.services_done }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase">services</p>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400 font-bold text-center py-8">Aucune donnée ce mois</p>
        </div>

        <!-- Busiest Hours -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
          <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
            <ClockIcon class="w-4 h-4 mr-2 text-amber-500"/> Heures de Pointe
          </h3>
          <div v-if="data.busiest_hours.length" class="h-64">
            <Bar :data="busiestHoursChartData" :options="hoursBarOptions" />
          </div>
          <p v-else class="text-sm text-slate-400 font-bold text-center py-8">Aucune donnée ce mois</p>
        </div>
      </div>

      <!-- Status Summary -->
      <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
          <BarChart3Icon class="w-4 h-4 mr-2 text-amber-500"/> Répartition par Statut
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="s in statusCards" :key="s.status" class="rounded-2xl p-5 text-center" :class="s.bg">
            <p class="text-3xl font-black" :class="s.textColor">{{ s.count }}</p>
            <p class="text-[10px] font-black uppercase tracking-widest mt-1" :class="s.labelColor">{{ s.label }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();

import {
  HammerIcon, CalendarIcon, RotateCwIcon, ClipboardListIcon, CheckCircleIcon,
  ClockIcon, WrenchIcon, BarChart3Icon, PieChartIcon, UsersIcon
} from 'lucide-vue-next';

import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  Title, Tooltip, Legend, ArcElement
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement);

const isLoading = ref(true);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const years = [2024, 2025, 2026, 2027];

const data = ref({
  month_name: '',
  summary: { total_jobs: 0, delivered_jobs: 0, pending_jobs: 0, total_services: 0, completed_services: 0, avg_completion_min: 0, avg_delivery_min: 0, delivery_rate: 0 },
  jobs_per_day: [],
  service_distribution: [],
  worker_performance: [],
  status_distribution: [],
  busiest_hours: [],
});

const loadStats = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/statistics/workshop', { params: { month: selectedMonth.value, year: selectedYear.value } });
    data.value = res.data;
  } catch (e) {
    toast.error('Erreur de chargement des stats atelier');
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadStats);

const formatMinutes = (min) => {
  if (!min || min === 0) return '—';
  if (min < 60) return min + ' min';
  const h = Math.floor(min / 60);
  const m = min % 60;
  return h + 'h' + (m > 0 ? ' ' + m + 'min' : '');
};

const maxWorkerServices = computed(() => {
  if (!data.value.worker_performance.length) return 0;
  return Math.max(...data.value.worker_performance.map(w => w.services_done));
});

// Status cards
const statusCards = computed(() => {
  const map = {};
  (data.value.status_distribution || []).forEach(s => { map[s.status] = s.count; });
  return [
    { status: 'waiting', label: 'En Attente', count: map['waiting'] || 0, bg: 'bg-amber-50', textColor: 'text-amber-600', labelColor: 'text-amber-500' },
    { status: 'in_progress', label: 'En Cours', count: map['in_progress'] || 0, bg: 'bg-blue-50', textColor: 'text-blue-600', labelColor: 'text-blue-500' },
    { status: 'done', label: 'Terminés', count: map['done'] || 0, bg: 'bg-emerald-50', textColor: 'text-emerald-600', labelColor: 'text-emerald-500' },
    { status: 'delivered', label: 'Livrés', count: map['delivered'] || 0, bg: 'bg-purple-50', textColor: 'text-purple-600', labelColor: 'text-purple-500' },
  ];
});

// Chart: Jobs per day
const jobsPerDayChartData = computed(() => ({
  labels: data.value.jobs_per_day.map(d => d.label),
  datasets: [{
    label: 'Jobs',
    data: data.value.jobs_per_day.map(d => d.count),
    backgroundColor: 'rgba(245, 158, 11, 0.3)',
    borderColor: 'rgb(245, 158, 11)',
    borderWidth: 2,
    borderRadius: 6,
    hoverBackgroundColor: 'rgba(245, 158, 11, 0.6)',
  }]
}));

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', titleFont: { weight: 'bold' }, bodyFont: { weight: 'bold' } } },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1, font: { weight: 'bold', size: 10 } }, grid: { color: '#f1f5f9' } },
    x: { ticks: { font: { weight: 'bold', size: 9 }, maxRotation: 45 }, grid: { display: false } }
  }
};

// Chart: Service distribution
const serviceColors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#ec4899', '#14b8a6', '#f97316'];
const serviceChartData = computed(() => ({
  labels: data.value.service_distribution.map(s => s.name),
  datasets: [{
    data: data.value.service_distribution.map(s => s.count),
    backgroundColor: data.value.service_distribution.map((_, i) => serviceColors[i % serviceColors.length]),
    borderWidth: 0,
    hoverOffset: 8,
  }]
}));

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { font: { weight: 'bold', size: 11 }, padding: 12, usePointStyle: true, pointStyleWidth: 10 } },
    tooltip: { backgroundColor: '#1e293b', titleFont: { weight: 'bold' }, bodyFont: { weight: 'bold' } }
  }
};

// Chart: Busiest hours
const busiestHoursChartData = computed(() => ({
  labels: data.value.busiest_hours.map(h => h.hour),
  datasets: [{
    label: 'Jobs',
    data: data.value.busiest_hours.map(h => h.count),
    backgroundColor: 'rgba(99, 102, 241, 0.3)',
    borderColor: 'rgb(99, 102, 241)',
    borderWidth: 2,
    borderRadius: 6,
    hoverBackgroundColor: 'rgba(99, 102, 241, 0.6)',
  }]
}));

const hoursBarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', titleFont: { weight: 'bold' }, bodyFont: { weight: 'bold' } } },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1, font: { weight: 'bold', size: 10 } }, grid: { color: '#f1f5f9' } },
    x: { ticks: { font: { weight: 'bold', size: 11 } }, grid: { display: false } }
  }
};
</script>
