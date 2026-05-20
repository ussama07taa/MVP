<template>
  <div class="min-h-screen bg-[#f1f5f9] font-sans selection:bg-brand-500 selection:text-white relative overflow-x-hidden">
    
    <!-- Ambient Background Blobs (Premium Touch) -->
    <div class="fixed top-0 left-0 w-full h-96 bg-gradient-to-b from-brand-100/50 to-transparent pointer-events-none -z-10"></div>
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-brand-400/20 blur-[100px] pointer-events-none -z-10"></div>
    <div class="absolute top-[20%] right-[-5%] w-[30%] h-[30%] rounded-full bg-emerald-400/10 blur-[100px] pointer-events-none -z-10"></div>

    <div class="max-w-[1600px] mx-auto p-6 lg:p-10 space-y-10">
      
      <!-- Top Header & Greeting -->
      <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 relative z-10">
        <div class="flex items-center space-x-5">
          <div class="w-16 h-16 bg-gradient-to-br from-brand-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-xl shadow-brand-500/30 transform hover:rotate-3 transition-transform duration-300">
            <LayoutGridIcon class="w-8 h-8 text-white" />
          </div>
          <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-100 mb-2">
              <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
              <span class="text-[10px] font-black text-brand-700 uppercase tracking-widest">En ligne & Synchronisé</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-none">
              Taaouati <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-indigo-600">Design</span>
            </h1>
            <p class="text-slate-500 font-bold mt-2 text-sm max-w-lg">Tableau de bord de direction • Résumé analytique de l'atelier.</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 text-slate-700 px-5 py-3 rounded-2xl font-black shadow-sm flex items-center text-sm">
            <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" /> {{ todayDate }}
          </div>
          <a href="/pos" class="group relative bg-slate-900 text-white px-8 py-3 rounded-2xl font-black shadow-xl shadow-slate-900/20 hover:shadow-2xl hover:shadow-slate-900/30 transition-all duration-300 active:scale-95 flex items-center overflow-hidden">
            <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-brand-500 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <PlusCircleIcon class="w-5 h-5 mr-2 relative z-10 group-hover:rotate-90 transition-transform duration-500" /> 
            <span class="relative z-10">Caisse Rapide</span>
          </a>
        </div>
      </header>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Column 1 & 2: Main Stats & Activity -->
        <div class="xl:col-span-8 space-y-8">
          
          <!-- Key Performance Indicators (Hero Cards) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Revenue Card -->
            <div class="relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] group hover:-translate-y-1 transition-all duration-300">
              <div class="absolute top-0 right-0 w-40 h-40 bg-brand-50 rounded-full -mr-16 -mt-16 opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
              <div class="absolute bottom-6 right-6 opacity-5 text-brand-600 group-hover:opacity-10 group-hover:rotate-12 transition-all duration-500">
                <BanknoteIcon class="w-24 h-24" />
              </div>
              
              <div class="relative z-10">
                <div class="flex justify-between items-start mb-6">
                  <div class="w-14 h-14 bg-gradient-to-br from-brand-100 to-brand-50 rounded-2xl flex items-center justify-center border border-brand-100 shadow-inner">
                    <TrendingUpIcon class="w-6 h-6 text-brand-600" />
                  </div>
                  <div class="px-3 py-1.5 rounded-xl text-xs font-black" :class="stats.revenue_growth >= 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'">
                    {{ stats.revenue_growth > 0 ? '+' : '' }}{{ stats.revenue_growth }}% vs M-1
                  </div>
                </div>
                
                <h3 class="text-slate-400 font-black uppercase text-xs tracking-widest mb-1">Chiffre d'Affaires (Mois)</h3>
                <div class="flex items-baseline gap-2">
                  <span class="text-5xl font-black text-slate-900 tracking-tight">{{ Number(stats.revenue_today).toFixed(2) }}</span>
                  <span class="text-xl font-bold text-slate-400">DH</span>
                </div>
              </div>
            </div>

            <!-- Profit Card (Glassmorphism Dark) -->
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800 p-8 rounded-3xl shadow-[0_20px_40px_rgb(0,0,0,0.2)] border border-slate-700 group hover:-translate-y-1 transition-all duration-300">
              <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl -mr-20 -mt-20 group-hover:bg-emerald-500/20 transition-colors duration-700"></div>
              <div class="absolute bottom-6 right-6 opacity-10 text-emerald-400 group-hover:opacity-20 group-hover:-rotate-12 transition-all duration-500">
                <ZapIcon class="w-24 h-24" />
              </div>

              <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                  <div class="flex justify-between items-start mb-6">
                    <div class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/5 shadow-inner">
                      <CoinsIcon class="w-6 h-6 text-emerald-400" />
                    </div>
                    <div class="px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                      Marge: {{ stats.margin_percent }}%
                    </div>
                  </div>
                  
                  <h3 class="text-slate-400 font-black uppercase text-xs tracking-widest mb-1">Bénéfice Net Réel (Post OPEX)</h3>
                  <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-black text-white tracking-tight">{{ Number(stats.profit_today).toFixed(2) }}</span>
                    <span class="text-xl font-bold text-slate-500">DH</span>
                  </div>
                </div>

                <!-- EXPENSES BREAKDOWN -->
                <div v-if="stats.salary_expenses > 0 || stats.purchase_expenses > 0" class="mt-6 pt-5 border-t border-slate-700/50 space-y-2">
                  <div class="flex justify-between items-center group/line">
                     <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover/line:text-slate-300 transition-colors">Marge brute</span>
                     <span class="text-sm text-slate-200 font-black">{{ Number(stats.gross_profit).toFixed(2) }} DH</span>
                  </div>
                  <div class="flex justify-between items-center group/line">
                     <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover/line:text-slate-300 transition-colors">Salaires & OPEX</span>
                     <span class="text-sm text-rose-400 font-black">-{{ Number(stats.salary_expenses).toFixed(2) }} DH</span>
                  </div>
                   <div class="flex justify-between items-center group/line">
                      <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover/line:text-slate-300 transition-colors">Achats Nets (Avoirs Déduits)</span>
                      <span class="text-sm text-amber-400 font-black">-{{ Number(stats.purchase_expenses).toFixed(2) }} DH</span>
                   </div>
                   <div v-if="stats.customer_returns > 0" class="flex justify-between items-center group/line">
                      <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover/line:text-slate-300 transition-colors">Retours Clients (Remboursements)</span>
                      <span class="text-sm text-rose-300 font-black">-{{ Number(stats.customer_returns).toFixed(2) }} DH</span>
                   </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Activity & Alerts Split -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Recent Activity Timeline -->
            <div class="bg-white p-8 rounded-3xl border border-slate-200/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] h-full flex flex-col">
              <div class="flex justify-between items-end mb-8">
                <div>
                  <h2 class="text-xl font-black text-slate-900 tracking-tight">Activité Récente</h2>
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Dernières opérations</p>
                </div>
                <Link href="/admin/orders" class="w-10 h-10 rounded-full bg-slate-50 hover:bg-brand-50 flex items-center justify-center text-slate-400 hover:text-brand-600 transition-colors">
                  <ArrowUpRightIcon class="w-5 h-5" />
                </Link>
              </div>
              
              <div class="relative flex-1">
                <div class="absolute left-5 top-4 bottom-4 w-px bg-slate-100"></div>
                <div class="space-y-6 relative z-10">
                  <div v-for="(activity, index) in alerts.recent_activity" :key="activity.id" class="flex items-start group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mr-4 border-4 border-white shadow-sm transition-transform group-hover:scale-110" :class="activity.type === 'order' ? 'bg-brand-100 text-brand-600' : 'bg-emerald-100 text-emerald-600'">
                      <FileTextIcon v-if="activity.type === 'order'" class="w-4 h-4" />
                      <CheckCircleIcon v-else class="w-4 h-4" />
                    </div>
                    <div class="flex-1 bg-slate-50/50 rounded-2xl p-4 border border-slate-100 group-hover:border-brand-200 group-hover:bg-brand-50/30 transition-colors">
                      <div class="flex justify-between items-start mb-1">
                        <p class="text-sm font-black text-slate-800">{{ activity.title }}</p>
                        <p class="text-sm font-black" :class="activity.type === 'order' ? 'text-brand-600' : 'text-emerald-600'">{{ Number(activity.amount).toFixed(2) }} DH</p>
                      </div>
                      <div class="flex justify-between items-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ activity.subtitle }}</p>
                        <p class="text-[10px] font-bold text-slate-400 bg-white px-2 py-0.5 rounded-md border border-slate-100">{{ activity.time }}</p>
                      </div>
                    </div>
                  </div>

                  <div v-if="!alerts.recent_activity || alerts.recent_activity.length === 0" class="py-12 text-center flex flex-col items-center justify-center">
                     <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                       <HistoryIcon class="w-8 h-8 text-slate-200" />
                     </div>
                     <p class="text-slate-400 font-bold">Aucune activité récente.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stock Health Section -->
            <div class="bg-white p-8 rounded-3xl border border-slate-200/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] h-full flex flex-col">
              <div class="flex justify-between items-end mb-8">
                <div>
                  <h2 class="text-xl font-black text-slate-900 tracking-tight">Santé du Stock</h2>
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Alertes Critiques</p>
                </div>
                <Link href="/admin/stock-canto" class="w-10 h-10 rounded-full bg-slate-50 hover:bg-brand-50 flex items-center justify-center text-slate-400 hover:text-brand-600 transition-colors">
                  <ArrowUpRightIcon class="w-5 h-5" />
                </Link>
              </div>
              
              <div class="space-y-6 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                <!-- Canto Alerts -->
                <Link v-for="canto in alerts.low_canto_stock" :key="'canto-'+canto.color_code" href="/admin/stock-canto" class="group block p-4 rounded-2xl hover:bg-red-50 transition-all border border-transparent hover:border-red-100">
                  <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center mr-3 border border-red-100">
                        <AlertTriangleIcon class="w-4 h-4 text-red-500" :class="Number(canto.total_length_remaining) <= 0 ? 'animate-pulse' : ''"/>
                      </div>
                      <div>
                        <span class="font-black text-slate-800 text-sm block leading-tight">Bandchant {{ canto.color_code }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ canto.finish_type }}</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <span v-if="Number(canto.total_length_remaining) <= 0" class="px-2 py-1 bg-red-600 text-white text-[10px] font-black rounded-md uppercase tracking-wider shadow-sm mb-1 inline-block">Rupture</span>
                      <span v-else class="text-sm font-black" :class="Number(canto.total_length_remaining) <= 0 ? 'text-red-600' : 'text-slate-600'">
                        {{ Number(canto.total_length_remaining).toFixed(1) }}m
                      </span>
                      <div class="text-[9px] font-bold text-slate-400 uppercase">Seuil: {{ canto.alert_threshold }}m</div>
                    </div>
                  </div>
                  <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden shadow-inner">
                    <div :class="Number(canto.total_length_remaining) <= 0 ? 'bg-red-500' : 'bg-gradient-to-r from-orange-400 to-red-500'" 
                      class="h-full rounded-full transition-all duration-1000" 
                      :style="{ width: Math.min(100, Math.max(5, (Number(canto.total_length_remaining) / (canto.alert_threshold || 1) * 100))) + '%' }"></div>
                  </div>
                </Link>

                <!-- Panel Alerts -->
                <Link v-for="panel in alerts.low_panel_stock" :key="'panel-'+panel.type + panel.color_code" href="/admin/stock-mdf" class="group block p-4 rounded-2xl hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100">
                  <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mr-3 border border-amber-100">
                        <AlertTriangleIcon class="w-4 h-4 text-amber-500" :class="Number(panel.quantity) <= 0 ? 'animate-pulse text-red-500' : ''"/>
                      </div>
                      <div>
                        <span class="font-black text-slate-800 text-sm block leading-tight">Panneau {{ panel.type }} {{ panel.color_code }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ panel.finish_type }}</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <span v-if="Number(panel.quantity) <= 0" class="px-2 py-1 bg-red-600 text-white text-[10px] font-black rounded-md uppercase tracking-wider shadow-sm mb-1 inline-block">Rupture</span>
                      <span v-else class="text-sm font-black" :class="Number(panel.quantity) <= 0 ? 'text-red-600' : 'text-slate-600'">
                        {{ panel.quantity }} pcs
                      </span>
                      <div class="text-[9px] font-bold text-slate-400 uppercase">Seuil: {{ panel.alert_threshold || 2 }}</div>
                    </div>
                  </div>
                  <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden shadow-inner">
                    <div :class="Number(panel.quantity) <= 0 ? 'bg-red-500' : 'bg-gradient-to-r from-amber-400 to-orange-500'" 
                      class="h-full rounded-full transition-all duration-1000" 
                      :style="{ width: Math.min(100, Math.max(5, (Number(panel.quantity) / (panel.alert_threshold || 2) * 100))) + '%' }"></div>
                  </div>
                </Link>
                
                <div v-if="alerts.low_canto_stock.length === 0 && alerts.low_panel_stock.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 py-10">
                   <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mb-4">
                     <ShieldCheckIcon class="w-10 h-10 text-emerald-400" />
                   </div>
                   <p class="font-black text-lg text-slate-700 tracking-tight">Stock Sécurisé</p>
                   <p class="text-sm font-bold mt-1">Aucune rupture critique détectée.</p>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Column 3: Secondary Stats & Analytics -->
        <div class="xl:col-span-4 space-y-6">
          
          <!-- Outstanding Balances (Debt & Credit) -->
          <div class="bg-white p-8 rounded-3xl border border-slate-200/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-8">
            <div>
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center border border-indigo-100">
                    <UsersIcon class="w-5 h-5 text-indigo-500" />
                  </div>
                  <h3 class="text-slate-400 font-black uppercase text-xs tracking-widest">Crédit Client (À Encaisser)</h3>
                </div>
              </div>
              <div class="flex items-baseline gap-2 pl-13">
                <span class="text-4xl font-black text-slate-900 tracking-tight">{{ Number(stats.total_credit_market).toFixed(2) }}</span>
                <span class="text-base font-bold text-slate-400">DH</span>
              </div>
            </div>

            <div class="h-px w-full bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

            <div>
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100">
                    <TruckIcon class="w-5 h-5 text-rose-500" />
                  </div>
                  <h3 class="text-slate-400 font-black uppercase text-xs tracking-widest">Dette Fournisseur (À Payer)</h3>
                </div>
              </div>
              <div class="flex items-baseline gap-2 pl-13">
                <span class="text-4xl font-black text-rose-600 tracking-tight">{{ Number(stats.total_supplier_debt).toFixed(2) }}</span>
                <span class="text-base font-bold text-rose-400">DH</span>
              </div>
            </div>
          </div>

          <!-- Revenue Breakdown (Analytical Card) -->
          <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-8 rounded-3xl text-white shadow-[0_20px_40px_rgba(0,0,0,0.2)] border border-slate-700 relative overflow-hidden group">
             <!-- Decorative elements -->
             <div class="absolute -right-10 -top-10 w-40 h-40 bg-brand-500/20 rounded-full blur-3xl"></div>
             <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-purple-500/20 rounded-full blur-3xl"></div>
             
             <div class="relative z-10">
               <div class="flex items-center justify-between mb-8">
                 <div class="flex items-center">
                   <div class="w-10 h-10 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/10 mr-3">
                     <PieChartIcon class="w-5 h-5 text-brand-300" />
                   </div>
                   <h3 class="font-black uppercase tracking-widest text-xs text-slate-300">Analyse des Revenus</h3>
                 </div>
               </div>
               
               <div class="space-y-6">
                 <div>
                   <div class="flex justify-between text-sm mb-2">
                     <span class="font-bold text-brand-200">Prestations de Services</span>
                     <span class="font-black text-white">{{ Number(stats.services_revenue_today).toFixed(2) }} <span class="text-[10px] text-brand-400">DH</span></span>
                   </div>
                   <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden shadow-inner border border-slate-800">
                     <div class="bg-gradient-to-r from-brand-400 to-brand-500 h-full rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(59,130,246,0.5)]" :style="{ width: (stats.revenue_today > 0 ? (stats.services_revenue_today / stats.revenue_today) * 100 : 0) + '%' }"></div>
                   </div>
                   <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2 text-right">
                     {{ (stats.revenue_today > 0 ? (stats.services_revenue_today / stats.revenue_today) * 100 : 0).toFixed(1) }}% du CA
                   </p>
                 </div>

                 <div>
                   <div class="flex justify-between text-sm mb-2">
                     <span class="font-bold text-purple-200">Vente de Matériaux</span>
                     <span class="font-black text-white">{{ Number(stats.materials_revenue_today).toFixed(2) }} <span class="text-[10px] text-purple-400">DH</span></span>
                   </div>
                   <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden shadow-inner border border-slate-800">
                     <div class="bg-gradient-to-r from-purple-400 to-purple-500 h-full rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(168,85,247,0.5)]" :style="{ width: (stats.revenue_today > 0 ? (stats.materials_revenue_today / stats.revenue_today) * 100 : 0) + '%' }"></div>
                   </div>
                   <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2 text-right">
                     {{ (stats.revenue_today > 0 ? (stats.materials_revenue_today / stats.revenue_today) * 100 : 0).toFixed(1) }}% du CA
                   </p>
                 </div>
               </div>
               
               <!-- Note on strategy -->
               <div class="mt-8 p-4 bg-white/5 rounded-2xl border border-white/10 flex items-start">
                 <InfoIcon class="w-4 h-4 text-brand-300 mr-3 mt-0.5 shrink-0" />
                 <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase tracking-wider">
                   Objectif: Maintenir un ratio élevé de services pour maximiser la marge nette de l'atelier.
                 </p>
               </div>
             </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { 
  LayoutGridIcon, TrendingUpIcon, BanknoteIcon, ZapIcon, CoinsIcon, 
  ShieldCheckIcon, AlertCircleIcon, CalendarIcon, PlusCircleIcon,
  ArrowUpRightIcon, ArrowRightIcon, HistoryIcon, TrendingDownIcon, PieChartIcon,
  FileTextIcon, CheckCircleIcon, AlertTriangleIcon, TruckIcon, UsersIcon, InfoIcon
} from 'lucide-vue-next';

const props = defineProps({
  stats: Object,
  alerts: Object
});

const todayDate = computed(() => {
  return new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}
</style>
