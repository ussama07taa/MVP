<template>
  <div class="max-w-7xl mx-auto pb-10 px-4">
    <!-- Header with Glassmorphism & Date Filter -->
    <header class="mb-10 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
      <div class="animate-in fade-in slide-in-from-left duration-700">
        <div class="flex items-center mb-1">
          <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200 mr-4">
            <PieChartIcon class="w-7 h-7 text-white"/>
          </div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Statistiques <span class="text-indigo-600">&amp;</span> Arbah</h1>
        </div>
        <p class="text-slate-400 font-medium ml-14 flex items-center">
           <CalendarIcon class="w-4 h-4 mr-2" />
           Rapport financier pour <span class="mx-1.5 font-black text-slate-700 uppercase">{{ stats.month_name || '...' }}</span>
        </p>
      </div>
      
      <div class="flex flex-wrap items-center gap-4 animate-in fade-in slide-in-from-right duration-700">
        <!-- Month/Year Selector -->
        <div class="flex items-center bg-white p-2 rounded-[22px] border border-slate-100 shadow-sm">
           <select v-model="selectedMonth" @change="loadStats" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
              <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
           </select>
           <div class="w-px h-4 bg-slate-100 mx-2"></div>
           <select v-model="selectedYear" @change="loadStats" class="bg-transparent border-none font-black text-slate-700 text-sm focus:ring-0 cursor-pointer px-4 appearance-none">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
           </select>
        </div>

        <button @click="exportStats" class="px-5 py-4 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 font-black rounded-2xl transition-all border border-emerald-200 shadow-sm flex items-center justify-center whitespace-nowrap active:scale-95" title="Exporter Rapport Excel">
          <FileDownIcon class="w-5 h-5 mr-2" /> Exporter
        </button>

        <!-- Actualiser Button (Master UX) -->
        <button @click="loadStats" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-4 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
        </button>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-40">
       <div class="relative">
          <div class="w-20 h-20 border-4 border-slate-100 rounded-full"></div>
          <div class="w-20 h-20 border-4 border-t-indigo-600 rounded-full animate-spin absolute top-0 left-0"></div>
       </div>
       <p class="mt-8 text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] animate-pulse">Analyse des flux financiers...</p>
    </div>

    <!-- Main Content -->
    <div v-else class="space-y-10 animate-in fade-in zoom-in-95 duration-1000">
      
      <!-- Top Layer: Primary Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Revenue Card -->
        <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.03)] border border-slate-50 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-1000"></div>
          <div class="relative z-10">
            <div class="flex justify-between items-start mb-10">
               <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                 <TrendingUpIcon class="w-6 h-6" />
               </div>
               <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Revenue Brut</span>
            </div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Chiffre d'Affaires</h3>
            <div class="text-5xl font-black text-slate-900 tracking-tighter mb-4">{{ format(stats.revenue) }}<span class="text-lg font-bold text-slate-300 ml-1">DH</span></div>
            
            <!-- Revenue Details (Breakdown) -->
            <div class="space-y-3 mt-6 border-t border-slate-50 pt-6">
               <div v-for="rev in stats.revenue_breakdown" :key="rev.type" class="flex justify-between items-center group/line">
                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover/line:text-indigo-600 transition-colors">{{ rev.type || 'Service' }}</span>
                  <span class="text-xs font-black text-slate-700">{{ format(rev.total) }} DH</span>
               </div>
               <!-- Returns highlight -->
               <div v-if="stats.customer_returns > 0" class="flex justify-between items-center pt-2 mt-1 border-t border-dashed border-rose-100">
                  <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest italic">Remboursements (Retours)</span>
                  <span class="text-xs font-black text-rose-600">-{{ format(stats.customer_returns) }} DH</span>
               </div>
               <!-- Cash Collected highlight -->
               <div class="flex justify-between items-center pt-2">
                  <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Encaissé (Cash)</span>
                  <span class="text-xs font-black text-emerald-600">{{ format(stats.cash_collected) }} DH</span>
               </div>
               <!-- Unpaid highlight -->
               <div v-if="stats.unpaid_revenue > 0" class="flex justify-between items-center pt-2 mt-1 border-t border-dashed border-slate-100">
                  <span class="text-[9px] font-black text-amber-500 uppercase">Restant à percevoir (Crédit)</span>
                  <span class="text-xs font-black text-amber-600">{{ format(stats.unpaid_revenue) }} DH</span>
               </div>
            </div>
          </div>
        </div>

        <!-- COGS Card -->
        <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.03)] border border-slate-50 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500 flex flex-col">
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-rose-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-1000"></div>
          <div class="relative z-10 flex-grow">
            <div class="flex justify-between items-start mb-10">
               <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
                 <TruckIcon class="w-6 h-6" />
               </div>
               <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Cout Sel3a</span>
            </div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Marchandises (COGS)</h3>
            <div class="text-5xl font-black text-rose-500 tracking-tighter italic mb-4">-{{ format(stats.cogs) }}<span class="text-lg font-bold text-rose-200 ml-1">DH</span></div>
            
            <!-- COGS Details -->
            <div class="space-y-3 mt-6 border-t border-slate-50 pt-6">
               <div v-for="cg in stats.cogs_breakdown" :key="cg.type" class="flex justify-between items-center group/line">
                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover/line:text-rose-600 transition-colors">{{ cg.type || 'Service' }}</span>
                  <span class="text-xs font-black text-slate-700">{{ format(cg.total) }} DH</span>
               </div>
            </div>
          </div>
        </div>

        <!-- Gross Margin Card - Premium Dark -->
        <div class="bg-slate-900 rounded-[45px] p-4 md:p-10 shadow-[0_20px_50px_rgba(15,23,42,0.2)] border border-slate-800 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 to-transparent"></div>
          <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>
          <div class="relative z-10">
            <div class="flex justify-between items-start mb-10">
               <div class="p-4 bg-indigo-600 text-white rounded-2xl shadow-xl shadow-indigo-900/40">
                 <ZapIcon class="w-6 h-6" />
               </div>
               <div class="flex flex-col items-end">
                  <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Atelier Efficiency</span>
                  <span class="text-lg font-black text-white">{{ stats.revenue > 0 ? Math.round(stats.gross_margin/stats.revenue*100) : 0 }}%</span>
               </div>
            </div>
            <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.2em] mb-2">Marge Brute</h3>
            <div class="text-5xl font-black text-white tracking-tighter mb-10">{{ format(stats.gross_margin) }}<span class="text-lg font-bold text-slate-500 ml-1">DH</span></div>
            
            <div class="space-y-4 bg-white/5 p-6 rounded-3xl border border-white/5">
               <div class="flex justify-between items-center mb-1">
                  <span class="text-[10px] font-black text-slate-400 uppercase">Ratio Revenue/Cout</span>
                  <span class="text-[10px] font-black text-emerald-400">+{{ getRatio() }}%</span>
               </div>
               <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                  <div class="h-full bg-indigo-500 transition-all duration-1000" :style="{ width: getPercentage(stats.gross_margin, stats.revenue) + '%' }"></div>
               </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cash Flow & Purchases Layer -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Net Cash Flow (Real Money) -->
        <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.03)] border border-slate-50 relative overflow-hidden group hover:-translate-y-1 transition-all duration-500">
           <div class="flex justify-between items-center mb-8">
              <div class="flex items-center">
                 <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl mr-4">
                   <CircleDollarSignIcon class="w-6 h-6" />
                 </div>
                 <div>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Trésorerie Réelle (Net Cash)</h3>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Ce qui reste f jibi (Encaissé - Payé)</p>
                 </div>
              </div>
           </div>
           <div class="text-4xl font-black tracking-tighter" :class="stats.net_cash_flow >= 0 ? 'text-emerald-600' : 'text-rose-600'">
             {{ stats.net_cash_flow >= 0 ? '' : '-' }}{{ format(Math.abs(stats.net_cash_flow)) }}<span class="text-lg font-bold opacity-50 ml-1">DH</span>
           </div>
           <div class="mt-6 grid grid-cols-2 gap-4">
              <div class="p-4 bg-slate-50 rounded-3xl">
                 <span class="text-[9px] font-black text-slate-400 uppercase block mb-1">Total Encaissé</span>
                 <span class="text-sm font-black text-emerald-600">+{{ format(stats.cash_collected) }} DH</span>
              </div>
              <div class="p-4 bg-slate-50 rounded-3xl">
                 <span class="text-[9px] font-black text-slate-400 uppercase block mb-1">Total Sorties</span>
                 <span class="text-sm font-black text-rose-600">-{{ format(stats.opex + stats.purchases_paid) }} DH</span>
              </div>
           </div>
        </div>

        <!-- Purchases Overview -->
        <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.03)] border border-slate-50 relative overflow-hidden group hover:-translate-y-1 transition-all duration-500">
           <div class="flex justify-between items-center mb-8">
              <div class="flex items-center">
                 <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl mr-4">
                   <TruckIcon class="w-6 h-6" />
                 </div>
                 <div>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Mbi3at Sel3a (Purchases)</h3>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Achats Matériaux & Fournisseurs</p>
                 </div>
              </div>
           </div>
           <div class="text-4xl font-black text-slate-900 tracking-tighter">
             {{ format(stats.total_purchases) }}<span class="text-lg font-bold text-slate-300 ml-1">DH</span>
           </div>
           <div class="mt-6 flex items-center justify-between">
              <div class="flex flex-col">
                 <span class="text-[9px] font-black text-slate-400 uppercase block mb-1">Payé aux Fournisseurs</span>
                 <span class="text-sm font-black text-indigo-600">{{ format(stats.purchases_paid) }} DH</span>
              </div>
              <div class="text-right">
                 <span class="text-[9px] font-black text-slate-400 uppercase block mb-1">Reste à payer (Dettes)</span>
                 <span class="text-sm font-black text-rose-500">{{ format(stats.total_purchases - stats.purchases_paid) }} DH</span>
              </div>
           </div>
        </div>
      </div>

      <!-- Middle Layer: Health & Top Clients -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Cash vs Credit Health Indicator -->
        <div class="lg:col-span-2 bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 relative overflow-hidden group">
          <div class="flex justify-between items-center mb-10">
             <div class="flex items-center">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl mr-4">
                  <BarChart3Icon class="w-6 h-6" />
                </div>
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Santé de Trésorerie</h3>
             </div>
             <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest">
                <span class="text-emerald-500 flex items-center"><div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div> Encaissé</span>
                <span class="text-amber-500 flex items-center"><div class="w-2 h-2 rounded-full bg-amber-500 mr-2"></div> Crédit</span>
             </div>
          </div>

          <div class="space-y-6">
            <div class="flex justify-between items-end mb-2">
               <span class="text-sm font-black text-slate-900">{{ getPercentage(stats.cash_collected, stats.revenue) }}% Encaissé</span>
               <span class="text-sm font-black text-slate-400">{{ getPercentage(stats.unpaid_revenue, stats.revenue) }}% Crédit</span>
            </div>
            <div class="w-full bg-amber-100 h-6 rounded-full overflow-hidden flex p-1 border border-amber-50/50 shadow-inner">
              <div :style="{ width: getPercentage(stats.cash_collected, stats.revenue) + '%' }" 
                   class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-200/50 transition-all duration-1000 ease-out relative group-hover:from-emerald-500 group-hover:to-emerald-700">
                   <div class="absolute inset-0 bg-white/10 opacity-10"></div>
              </div>
            </div>
            <p class="text-[11px] text-slate-400 font-medium italic leading-relaxed">
              * Idéalement, gardez votre taux de crédit sous les 20% pour assurer une liquidité fluide.
            </p>
          </div>
        </div>

        <!-- Top Clients Leaderboard -->
        <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50">
           <div class="flex items-center mb-10">
              <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl mr-4">
                <UsersIcon class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Top Clients</h3>
           </div>
           
           <div class="space-y-6">
              <div v-for="(client, index) in stats.top_clients" :key="index" 
                   class="flex items-center justify-between p-4 rounded-3xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 group">
                  <div class="flex items-center space-x-4">
                      <div class="w-10 h-10 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-xs font-black"
                           :class="index === 0 ? 'text-amber-500 border-amber-100' : 'text-slate-400'">
                          #{{ index + 1 }}
                      </div>
                      <div class="flex flex-col">
                        <span class="font-black text-slate-700 text-sm group-hover:text-indigo-600 transition-colors">{{ client.name }}</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Fidèle ce mois</span>
                      </div>
                  </div>
                  <div class="text-right">
                    <span class="block font-black text-indigo-600 text-sm">{{ format(client.total_spent) }} <span class="text-[9px] text-indigo-300 ml-0.5 uppercase">DH</span></span>
                  </div>
              </div>
              <div v-if="!stats.top_clients || stats.top_clients.length === 0" class="py-10 text-center text-slate-300 italic text-xs uppercase tracking-widest">
                 Aucun client ce mois
              </div>
           </div>
        </div>
      </div>

      <!-- New Section: Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Line Chart: Évolution Quotidienne -->
          <div class="lg:col-span-2 bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50">
              <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-10 flex items-center">
                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl mr-3">
                  <TrendingUpIcon class="w-5 h-5" />
                </div>
                Évolution Quotidienne (Revenue)
              </h3>
              <div class="h-80 w-full">
                  <Line :data="lineChartData" :options="chartOptions" />
              </div>
          </div>
          
          <!-- Doughnut Chart: Répartition des Revenus -->
          <div class="bg-white rounded-[45px] p-4 md:p-10 shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50">
              <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-10 flex items-center">
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl mr-3">
                  <PieChartIcon class="w-5 h-5" />
                </div>
                Répartition
              </h3>
              <div class="h-80 w-full relative">
                  <Doughnut :data="doughnutChartData" :options="doughnutOptions" />
              </div>
          </div>
      </div>

      <!-- Bottom Layer: Activity & OPEX -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
        
        <!-- Recent Activity Timeline -->
        <div class="lg:col-span-4 bg-white rounded-[45px] shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 overflow-hidden flex flex-col">
            <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30">
               <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center">
                  <ClockIcon class="w-5 h-5 text-slate-400 mr-3" />
                  Dernières Mbi3at (Sales)
               </h3>
            </div>
            <div class="p-10 space-y-8 flex-grow overflow-y-auto max-h-[500px]">
                <div v-for="order in stats.recent_orders" :key="order.id" class="relative pl-8 border-l-2 border-slate-100 group">
                    <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-2 border-slate-200 group-hover:border-emerald-500 group-hover:scale-125 transition-all duration-300"></div>
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-sm font-black text-slate-700 group-hover:text-emerald-600 transition-colors">{{ order.client }}</span>
                        <span class="text-sm font-black text-slate-900 tracking-tight">{{ format(order.amount) }} <span class="text-[9px] text-slate-400">DH</span></span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ order.time }}</p>
                </div>
                <div v-if="!stats.recent_orders || stats.recent_orders.length === 0" class="py-20 text-center text-slate-300 italic text-xs uppercase tracking-widest">
                   Aucune vente récente
                </div>
            </div>
        </div>

        <!-- OPEX Section -->
        <div class="lg:col-span-4 bg-white rounded-[45px] shadow-[0_8px_40px_rgb(0,0,0,0.02)] border border-slate-50 overflow-hidden flex flex-col">
          <div class="px-10 py-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <div class="flex items-center">
               <ReceiptIcon class="w-5 h-5 text-slate-400 mr-3" />
               <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Charges (OPEX)</h3>
            </div>
            <div class="flex flex-col items-end">
               <span class="text-xl font-black text-rose-500 tracking-tight">-{{ format(stats.opex) }} <span class="text-[10px] text-rose-300 uppercase ml-0.5">DH</span></span>
            </div>
          </div>
          <div class="p-10 flex-grow">
            <div v-if="stats.expense_breakdown && stats.expense_breakdown.length > 0" class="space-y-8">
              <div v-for="cat in stats.expense_breakdown" :key="cat.category" class="group">
                <div class="flex justify-between items-end mb-3">
                   <div class="flex items-center">
                     <div class="w-1.5 h-1.5 rounded-full mr-2" :style="{ backgroundColor: getCatColor(cat.category) }"></div>
                     <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">{{ cat.category || 'N/A' }}</span>
                   </div>
                   <span class="text-xs font-black text-slate-900 tracking-tight">{{ format(cat.total) }} DH</span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-200/50">
                   <div class="h-full rounded-full transition-all duration-1000 shadow-sm" 
                        :style="{ width: getPercentage(cat.total, stats.opex) + '%', backgroundColor: getCatColor(cat.category) }">
                   </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- FINAL PROFIT CARD: THE MASTERPIECE -->
        <div class="lg:col-span-4 bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-[45px] shadow-[0_25px_60px_rgba(16,185,129,0.25)] p-4 md:p-10 flex flex-col relative overflow-hidden group">
          <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
          <div class="relative z-10 h-full flex flex-col">
               <div class="flex items-center gap-3 mb-8">
                  <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
                    <CircleDollarSignIcon class="w-6 h-6 text-white" />
                  </div>
                  <span class="text-[10px] font-black text-emerald-100 uppercase tracking-[0.2em]">Net Profit</span>
               </div>
               <div class="text-4xl font-black text-white tracking-tighter mb-4">
                  {{ format(stats.net_profit) }}<span class="text-xl font-bold text-emerald-200 ml-1">DH</span>
               </div>
               <div class="mt-auto pt-6 border-t border-white/10">
                   <div class="flex justify-between items-center mb-2">
                       <span class="text-[9px] font-black text-emerald-200 uppercase tracking-widest">Rentabilité</span>
                       <span class="text-lg font-black text-white">{{ stats.margin_percentage || 0 }}%</span>
                   </div>
                   <div class="w-full bg-emerald-900/40 h-2 rounded-full overflow-hidden p-0.5 border border-emerald-500/20">
                        <div class="h-full rounded-full bg-white shadow-[0_0_10px_white]" :style="{ width: Math.min((stats.margin_percentage || 0) * 2, 100) + '%' }"></div>
                   </div>
               </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  PieChartIcon, RotateCwIcon, TrendingUpIcon, TruckIcon, 
  ReceiptIcon, CheckCircleIcon, CalendarIcon, ZapIcon,
  CircleDollarSignIcon, BarChart3Icon, UsersIcon, ClockIcon, FileDownIcon
} from 'lucide-vue-next';

// Chart.js imports
import { Line, Doughnut } from 'vue-chartjs';
import { 
  Chart as ChartJS, CategoryScale, LinearScale, PointElement, 
  LineElement, Title, Tooltip, Legend, ArcElement, Filler
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement, Filler);

const stats = ref({
    revenue: 0,
    cash_collected: 0,
    unpaid_revenue: 0,
    total_purchases: 0,
    purchases_paid: 0,
    net_cash_flow: 0,
    cogs: 0,
    gross_margin: 0,
    opex: 0,
    net_profit: 0,
    margin_percentage: 0,
    revenue_breakdown: [],
    cogs_breakdown: [],
    expense_breakdown: [],
    customer_returns: 0,
    top_clients: [],
    daily_revenue: [],
    recent_orders: []
});
const isLoading = ref(true);

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
const years = [2024, 2025, 2026];

// Computed Chart Data
const lineChartData = computed(() => ({
  labels: stats.value.daily_revenue ? stats.value.daily_revenue.map(d => d.date.split('-')[2]) : [],
  datasets: [{
    label: 'Revenue (DH)',
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
    borderColor: '#6366f1',
    borderWidth: 4,
    pointBackgroundColor: '#fff',
    pointBorderColor: '#6366f1',
    pointHoverRadius: 6,
    data: stats.value.daily_revenue ? stats.value.daily_revenue.map(d => d.total) : [],
    tension: 0.4,
    fill: true
  }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { display: false },
        x: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 10 } } }
    }
};

const doughnutChartData = computed(() => {
  const data = stats.value.revenue_breakdown || [];
  return {
    labels: data.map(item => item.type),
    datasets: [{
      backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
      borderWidth: 0,
      hoverOffset: 20,
      data: data.map(item => item.total)
    }]
  };
});

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '75%',
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { weight: 'bold', size: 10 } } } }
};

const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};

const getRatio = () => {
    if (!stats.value.cogs || stats.value.cogs === 0) return 0;
    return Math.round((stats.value.revenue / stats.value.cogs - 1) * 100);
};

const getCatColor = (cat) => {
  const c = (cat || '').toLowerCase();
  if (c.includes('salaire')) return '#6366f1'; // Indigo
  if (c.includes('loyer') || c.includes('fixe')) return '#f43f5e'; // Rose/Red
  if (c.includes('matiere') || c.includes('stock')) return '#f59e0b'; // Amber
  if (c.includes('transport')) return '#0ea5e9'; // Sky
  return '#94a3b8'; // Slate
};

const loadStats = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/statistics/financial', {
        params: {
            month: selectedMonth.value,
            year: selectedYear.value
        }
    });
    stats.value = res.data;
  } catch (e) { 
    console.error(e); 
  } finally { 
    isLoading.value = false; 
  }
};

const format = (val) => {
  const num = parseFloat(val);
  return isNaN(num) ? '0.00' : num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const exportStats = async () => {
    try {
        const res = await axios.post(`/api/export/profits`, null, {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            },
            responseType: 'blob'
        });
        
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `rapport_profits_${selectedMonth.value}_${selectedYear.value}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error(e);
        toast.error('Erreur lors du téléchargement de l\'export');
    }
};

onMounted(() => loadStats());
</script>

<style scoped>
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 3s linear infinite;
}
select { background-image: none; }
</style>
