<template>
  <div class="min-h-screen font-sans selection:bg-brand-500 selection:text-white">

    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Rapports Mensuels</h1>
        <p class="text-sm font-bold text-slate-400 mt-1">Générez et téléchargez vos rapports PDF</p>
      </div>
    </header>

    <!-- Generator Card -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
      <div class="p-8 border-b border-slate-100">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-14 h-14 bg-gradient-to-br from-brand-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/20">
            <FileTextIcon class="w-7 h-7 text-white" />
          </div>
          <div>
            <h2 class="text-xl font-black text-slate-900">Générer un Rapport</h2>
            <p class="text-xs font-bold text-slate-400 mt-0.5">Sélectionnez le mois et téléchargez le PDF complet</p>
          </div>
        </div>

        <!-- Month/Year Selector -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div class="space-y-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Mois</label>
            <select v-model="selectedMonth" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Année</label>
            <select v-model="selectedYear" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div class="flex gap-3">
            <button @click="previewReport" :disabled="isLoading"
              class="flex-1 px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-black text-sm transition-all flex items-center justify-center">
              <EyeIcon class="w-4 h-4 mr-2" /> Aperçu
            </button>
            <button @click="downloadReport" :disabled="isDownloading"
              class="flex-1 px-6 py-3.5 bg-gradient-to-r from-brand-600 to-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-brand-500/25 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center disabled:opacity-50">
              <Loader2Icon v-if="isDownloading" class="w-4 h-4 mr-2 animate-spin" />
              <DownloadIcon v-else class="w-4 h-4 mr-2" />
              Télécharger PDF
            </button>
          </div>
        </div>
      </div>

      <!-- Preview Panel -->
      <div v-if="preview" class="p-8 bg-slate-50/50">
        <div class="flex items-center gap-2 mb-6">
          <BarChart3Icon class="w-5 h-5 text-brand-500" />
          <h3 class="font-black text-lg text-slate-900">Aperçu — {{ preview.month_name }}</h3>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">CA Net</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ formatNumber(preview.financial.revenue) }}</p>
            <p class="text-[10px] font-bold text-slate-400">DH</p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Bénéfice Net</p>
            <p class="text-xl font-black mt-1" :class="preview.financial.net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatNumber(preview.financial.net_profit) }}</p>
            <p class="text-[10px] font-bold text-slate-400">DH</p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Marge</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ preview.financial.margin_percentage }}%</p>
            <p class="text-[10px] font-bold text-slate-400">net</p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Commandes</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ preview.financial.order_count }}</p>
            <p class="text-[10px] font-bold text-slate-400">ce mois</p>
          </div>
        </div>

        <!-- Additional Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100">
              <TrendingUpIcon class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <p class="text-xs font-black text-slate-900">{{ formatNumber(preview.financial.cash_collected) }} DH</p>
              <p class="text-[9px] font-bold text-slate-400 uppercase">Encaissé</p>
            </div>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100">
              <AlertCircleIcon class="w-5 h-5 text-rose-500" />
            </div>
            <div>
              <p class="text-xs font-black text-slate-900">{{ formatNumber(preview.financial.unpaid_revenue) }} DH</p>
              <p class="text-[9px] font-bold text-slate-400 uppercase">Impayés</p>
            </div>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200/60 p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center border border-blue-100">
              <WalletIcon class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p class="text-xs font-black text-slate-900" :class="preview.financial.net_cash_flow >= 0 ? '' : 'text-rose-600'">{{ formatNumber(preview.financial.net_cash_flow) }} DH</p>
              <p class="text-[9px] font-bold text-slate-400 uppercase">Trésorerie</p>
            </div>
          </div>
        </div>

        <p class="text-[10px] font-bold text-slate-400 mt-4 text-center">Téléchargez le PDF pour le rapport détaillé (clients, services, stock, employés...)</p>
      </div>
    </div>

    <!-- Info Card -->
    <div class="mt-6 bg-slate-900 rounded-3xl p-6 text-white">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
          <InfoIcon class="w-5 h-5 text-slate-300" />
        </div>
        <div>
          <h4 class="font-black text-sm mb-2">Contenu du Rapport PDF</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-400 font-bold">
            <p>• Résumé financier complet (CA, COGS, Marge, Profit)</p>
            <p>• Top 5 clients du mois</p>
            <p>• Indicateurs clés (panier moyen, trésorerie)</p>
            <p>• Services les plus demandés</p>
            <p>• Charges détaillées par catégorie</p>
            <p>• Performance des employés</p>
            <p>• État du stock (panneaux + bandchant)</p>
            <p>• Alertes stock critique</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { FileTextIcon, DownloadIcon, EyeIcon, Loader2Icon, BarChart3Icon, TrendingUpIcon, AlertCircleIcon, WalletIcon, InfoIcon } from 'lucide-vue-next';

const now = new Date();
const selectedMonth = ref(now.getMonth() + 1);
const selectedYear = ref(now.getFullYear());
const isLoading = ref(false);
const isDownloading = ref(false);
const preview = ref(null);

const months = [
  { value: 1, label: 'Janvier' }, { value: 2, label: 'Février' }, { value: 3, label: 'Mars' },
  { value: 4, label: 'Avril' }, { value: 5, label: 'Mai' }, { value: 6, label: 'Juin' },
  { value: 7, label: 'Juillet' }, { value: 8, label: 'Août' }, { value: 9, label: 'Septembre' },
  { value: 10, label: 'Octobre' }, { value: 11, label: 'Novembre' }, { value: 12, label: 'Décembre' },
];

const years = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i);

const formatNumber = (n) => {
  if (n == null) return '0,00';
  return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const previewReport = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/reports/preview', { params: { month: selectedMonth.value, year: selectedYear.value } });
    preview.value = res.data;
  } catch (e) { toast.error('Erreur lors du chargement de l\'aperçu.'); }
  finally { isLoading.value = false; }
};

const downloadReport = async () => {
  isDownloading.value = true;
  try {
    const res = await axios.get('/api/admin/reports/generate', {
      params: { month: selectedMonth.value, year: selectedYear.value },
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `rapport-mensuel-${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    toast.success('Rapport téléchargé !');
  } catch (e) { toast.error('Erreur lors de la génération du rapport.'); }
  finally { isDownloading.value = false; }
};
</script>
