<template>
  <div class="max-w-5xl mx-auto pb-10 px-4">
    <!-- Header -->
    <header class="mb-10">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <div class="flex items-center mb-1">
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-3.5 rounded-2xl shadow-lg shadow-emerald-200 mr-4">
              <DatabaseIcon class="w-7 h-7 text-white"/>
            </div>
            <div>
              <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Sauvegardes</h1>
              <p class="text-slate-400 font-medium text-sm mt-0.5">Protection des données de l'atelier</p>
            </div>
          </div>
        </div>
        <button @click="loadBackups" :disabled="isLoading" class="group p-4 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-300 active:scale-90">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-emerald-600 transition-transform duration-500" />
        </button>
      </div>
    </header>

    <!-- Loading -->
    <div v-if="isLoading && !backups.length" class="flex flex-col items-center justify-center py-40">
      <div class="relative">
        <div class="w-20 h-20 border-4 border-slate-100 rounded-full"></div>
        <div class="w-20 h-20 border-4 border-t-emerald-500 rounded-full animate-spin absolute top-0 left-0"></div>
      </div>
      <p class="mt-8 text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] animate-pulse">Chargement des sauvegardes...</p>
    </div>

    <div v-else class="space-y-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 relative overflow-hidden">
          <div class="absolute -top-6 -right-6 w-24 h-24 bg-emerald-50 rounded-full opacity-60"></div>
          <div class="relative">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                <ArchiveIcon class="w-5 h-5 text-emerald-600"/>
              </div>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Backups</span>
            </div>
            <p class="text-4xl font-black text-slate-900">{{ meta.count }}</p>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 relative overflow-hidden">
          <div class="absolute -top-6 -right-6 w-24 h-24 bg-blue-50 rounded-full opacity-60"></div>
          <div class="relative">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                <HardDriveIcon class="w-5 h-5 text-blue-600"/>
              </div>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Espace Utilisé</span>
            </div>
            <p class="text-4xl font-black text-slate-900">{{ meta.total_size }}</p>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-50 relative overflow-hidden">
          <div class="absolute -top-6 -right-6 w-24 h-24 bg-amber-50 rounded-full opacity-60"></div>
          <div class="relative">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                <ClockIcon class="w-5 h-5 text-amber-600"/>
              </div>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dernier Backup</span>
            </div>
            <p class="text-lg font-black text-slate-900">{{ backups.length ? backups[0].age : 'Aucun' }}</p>
            <p v-if="backups.length" class="text-xs font-bold text-slate-400 mt-0.5">{{ backups[0].date }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-50">
        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center">
          <ZapIcon class="w-4 h-4 mr-2 text-amber-500"/> Actions
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- DB Only Backup -->
          <button @click="runBackup('db')" :disabled="isRunning"
            class="group relative flex flex-col items-center p-6 rounded-2xl border-2 border-dashed transition-all duration-300 active:scale-95"
            :class="isRunning ? 'border-slate-200 opacity-50 cursor-not-allowed' : 'border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50/50'">
            <div class="w-14 h-14 bg-emerald-100 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
              <DatabaseIcon v-if="!isRunning || runType !== 'db'" class="w-7 h-7 text-emerald-600"/>
              <RotateCwIcon v-else class="w-7 h-7 text-emerald-600 animate-spin"/>
            </div>
            <span class="font-black text-slate-800 text-sm">Backup Base de Données</span>
            <span class="text-[10px] font-bold text-slate-400 mt-1">Rapide (~5 sec)</span>
          </button>

          <!-- Full Backup -->
          <button @click="runBackup('full')" :disabled="isRunning"
            class="group relative flex flex-col items-center p-6 rounded-2xl border-2 border-dashed transition-all duration-300 active:scale-95"
            :class="isRunning ? 'border-slate-200 opacity-50 cursor-not-allowed' : 'border-blue-200 hover:border-blue-400 hover:bg-blue-50/50'">
            <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
              <ArchiveIcon v-if="!isRunning || runType !== 'full'" class="w-7 h-7 text-blue-600"/>
              <RotateCwIcon v-else class="w-7 h-7 text-blue-600 animate-spin"/>
            </div>
            <span class="font-black text-slate-800 text-sm">Backup Complet</span>
            <span class="text-[10px] font-bold text-slate-400 mt-1">DB + Fichiers (~30 sec)</span>
          </button>

          <!-- Clean Old -->
          <button @click="cleanBackups" :disabled="isRunning"
            class="group relative flex flex-col items-center p-6 rounded-2xl border-2 border-dashed transition-all duration-300 active:scale-95"
            :class="isRunning ? 'border-slate-200 opacity-50 cursor-not-allowed' : 'border-amber-200 hover:border-amber-400 hover:bg-amber-50/50'">
            <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
              <Trash2Icon v-if="!isRunning || runType !== 'clean'" class="w-7 h-7 text-amber-600"/>
              <RotateCwIcon v-else class="w-7 h-7 text-amber-600 animate-spin"/>
            </div>
            <span class="font-black text-slate-800 text-sm">Nettoyer les Anciens</span>
            <span class="text-[10px] font-bold text-slate-400 mt-1">Supprime les anciens backups</span>
          </button>
        </div>

        <!-- Running Status -->
        <div v-if="isRunning" class="mt-6 bg-slate-50 rounded-2xl p-4 flex items-center gap-3">
          <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
          <p class="text-sm font-bold text-slate-600">{{ runMessage }}</p>
        </div>
      </div>

      <!-- Backup List -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-50 overflow-hidden">
        <div class="p-8 pb-4">
          <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center">
            <ArchiveIcon class="w-4 h-4 mr-2 text-emerald-500"/> Historique des Sauvegardes
          </h2>
        </div>

        <div v-if="!backups.length" class="px-8 pb-12 text-center">
          <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-4">
            <InboxIcon class="w-10 h-10 text-slate-300"/>
          </div>
          <p class="font-black text-slate-400 text-sm">Aucun backup trouvé</p>
          <p class="text-xs text-slate-300 font-bold mt-1">Créez votre premier backup avec les boutons ci-dessus</p>
        </div>

        <div v-else class="divide-y divide-slate-50">
          <div v-for="(backup, index) in backups" :key="backup.path"
            class="px-8 py-5 flex items-center justify-between hover:bg-slate-50/50 transition-colors group">
            <div class="flex items-center gap-4 min-w-0 flex-1">
              <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="index === 0 ? 'bg-emerald-100' : 'bg-slate-100'">
                <FileArchiveIcon class="w-5 h-5" :class="index === 0 ? 'text-emerald-600' : 'text-slate-400'"/>
              </div>
              <div class="min-w-0">
                <p class="font-black text-slate-800 text-sm truncate">{{ backup.filename }}</p>
                <div class="flex items-center gap-3 mt-0.5">
                  <span class="text-[10px] font-bold text-slate-400">{{ backup.date }}</span>
                  <span class="text-[10px] font-bold text-slate-300">•</span>
                  <span class="text-[10px] font-bold text-slate-400">{{ backup.age }}</span>
                  <span v-if="index === 0" class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full uppercase tracking-wider">Dernier</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs font-black text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">{{ backup.size_human }}</span>
              <button @click="downloadBackup(backup)" title="Télécharger"
                class="p-2.5 rounded-xl hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 transition-all opacity-0 group-hover:opacity-100">
                <DownloadIcon class="w-4 h-4"/>
              </button>
              <button @click="deleteBackup(backup)" title="Supprimer"
                class="p-2.5 rounded-xl hover:bg-red-50 text-slate-400 hover:text-red-600 transition-all opacity-0 group-hover:opacity-100">
                <Trash2Icon class="w-4 h-4"/>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Card -->
      <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-8 text-white">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5">
            <InfoIcon class="w-5 h-5 text-emerald-400"/>
          </div>
          <div>
            <h3 class="font-black text-sm mb-2">Sauvegarde Automatique</h3>
            <p class="text-slate-400 text-sm font-medium leading-relaxed">
              Le système est configuré pour créer un backup automatique <span class="text-white font-black">chaque nuit à 1h30</span> 
              et nettoyer les anciens backups à 1h00. Les backups sont conservés <span class="text-white font-black">7 jours</span> complets, 
              puis un par jour pendant 16 jours, un par semaine pendant 8 semaines.
            </p>
            <div class="mt-4 bg-white/5 rounded-xl p-4 font-mono text-xs text-slate-400">
              <p class="text-emerald-400 font-bold mb-1"># Cron job à ajouter sur le serveur :</p>
              <p class="text-white">* * * * * cd /path/to/MVP && php artisan schedule:run >> /dev/null 2>&1</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();

import {
  DatabaseIcon, RotateCwIcon, ArchiveIcon, HardDriveIcon, ClockIcon,
  ZapIcon, Trash2Icon, DownloadIcon, FileArchiveIcon, InboxIcon, InfoIcon
} from 'lucide-vue-next';

const isLoading = ref(true);
const isRunning = ref(false);
const runType = ref('');
const runMessage = ref('');
const backups = ref([]);
const meta = ref({ count: 0, total_size: '0 B', disk: 'local', path: '' });

const loadBackups = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/backups');
    backups.value = res.data.backups;
    meta.value = { count: res.data.count, total_size: res.data.total_size, disk: res.data.disk, path: res.data.path };
  } catch (e) {
    toast.error('Erreur de chargement des backups');
  } finally {
    isLoading.value = false;
  }
};

const runBackup = async (type) => {
  isRunning.value = true;
  runType.value = type;
  runMessage.value = type === 'db' ? 'Sauvegarde de la base de données en cours...' : 'Sauvegarde complète en cours (DB + fichiers)...';
  try {
    const url = type === 'db' ? '/api/admin/backups/run-db' : '/api/admin/backups/run-full';
    const res = await axios.post(url);
    toast.success(res.data.message);
    await loadBackups();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Erreur lors du backup');
  } finally {
    isRunning.value = false;
    runType.value = '';
    runMessage.value = '';
  }
};

const cleanBackups = async () => {
  isRunning.value = true;
  runType.value = 'clean';
  runMessage.value = 'Nettoyage des anciens backups...';
  try {
    const res = await axios.post('/api/admin/backups/clean');
    toast.success(res.data.message);
    await loadBackups();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Erreur lors du nettoyage');
  } finally {
    isRunning.value = false;
    runType.value = '';
    runMessage.value = '';
  }
};

const downloadBackup = (backup) => {
  window.open('/api/admin/backups/download?path=' + encodeURIComponent(backup.path), '_blank');
};

const deleteBackup = async (backup) => {
  if (!confirm('Supprimer ce backup ?\n' + backup.filename)) return;
  try {
    await axios.delete('/api/admin/backups', { data: { path: backup.path } });
    toast.success('Backup supprimé');
    await loadBackups();
  } catch (e) {
    toast.error('Erreur lors de la suppression');
  }
};

onMounted(loadBackups);
</script>
