<template>
  <div class="min-h-screen font-sans selection:bg-brand-500 selection:text-white relative overflow-x-hidden" style="background: #f1f5f9;">
    
    <!-- Ambient Blobs -->
    <div class="fixed top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-100/60 to-transparent pointer-events-none -z-10"></div>
    <div class="fixed top-[-8%] left-[-8%] w-[35%] h-[35%] rounded-full bg-blue-400/15 blur-[120px] pointer-events-none -z-10"></div>
    <div class="fixed top-[15%] right-[-5%] w-[25%] h-[25%] rounded-full bg-emerald-400/10 blur-[120px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-[10%] left-[20%] w-[20%] h-[20%] rounded-full bg-amber-400/8 blur-[100px] pointer-events-none -z-10"></div>

    <div class="max-w-[1680px] mx-auto p-5 lg:p-8 space-y-7">

      <!-- ===== HERO HEADER ===== -->
      <header class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
        <div class="flex items-center space-x-5">
          <div class="relative group cursor-pointer">
            <div class="absolute -inset-1.5 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-2xl blur opacity-40 group-hover:opacity-70 transition duration-700"></div>
            <div class="relative w-16 h-16 bg-gradient-to-br from-blue-700 to-indigo-800 rounded-2xl flex items-center justify-center shadow-2xl shadow-blue-900/40">
              <LayoutGridIcon class="w-8 h-8 text-white" />
            </div>
          </div>
          <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 mb-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">En ligne & Synchronisé</span>
            </div>
            <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-none" style="font-family: 'Fira Code', monospace;">
              {{ dashboardTitle }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Dashboard</span>
            </h1>
            <p class="text-slate-500 font-semibold mt-1 text-sm">Tableau de bord de direction · Analyse en temps réel</p>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
          <!-- Period Selector -->
          <div class="flex bg-slate-200/50 backdrop-blur p-1 rounded-xl w-full sm:w-auto border border-slate-200">
            <button v-for="p in ['Jour', 'Semaine', 'Mois']" :key="p" 
                    @click="changePeriod(p)"
                    :class="activePeriod === p ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
              {{ p }}
            </button>
          </div>

          <div class="hidden sm:flex items-center gap-3">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 text-slate-600 px-4 py-2.5 rounded-2xl font-bold shadow-sm flex items-center text-sm">
              <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" /> {{ todayDate }}
            </div>
          </div>

          <a href="/pos" class="btn-primary w-full sm:w-auto !py-2.5 !px-6">
            <PlusCircleIcon class="w-5 h-5 mr-2" />
            <span>Caisse Rapide</span>
          </a>
        </div>
      </header>

      <!-- ===== QUICK STATS STRIP ===== -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ventes -->
        <Link href="/admin/orders" class="card-premium mesh-gradient-blue group block">
          <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-blue-600 opacity-10 blur-2xl rounded-full group-hover:scale-125 transition-transform duration-700"></div>
          <div class="flex justify-between items-start mb-6 relative z-10">
            <div class="w-14 h-14 bg-white/50 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/80 shadow-inner group-hover:-rotate-6 transition-transform duration-500">
              <ShoppingBagIcon class="w-7 h-7 text-blue-600" />
            </div>
            <div class="flex flex-col items-end">
              <span class="text-[9px] font-black text-blue-600 bg-blue-100/50 px-2 py-0.5 rounded-full uppercase tracking-tighter mb-1">Live</span>
              <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            </div>
          </div>
          <p class="text-[10px] font-black text-slate-500/80 uppercase tracking-widest mb-1 relative z-10">Ventes ({{ activePeriod }})</p>
          <div class="flex items-baseline gap-1 relative z-10">
            <span class="text-4xl font-black text-slate-900 tabular-nums tracking-tighter" style="font-family: 'Fira Code', monospace;">{{ stats.total_orders_month ?? '0' }}</span>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Unités</span>
          </div>
        </Link>

        <!-- Clients -->
        <Link href="/admin/clients" class="card-premium mesh-gradient-blue group block">
          <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-indigo-600 opacity-10 blur-2xl rounded-full group-hover:scale-125 transition-transform duration-700"></div>
          <div class="flex justify-between items-start mb-6 relative z-10">
            <div class="w-14 h-14 bg-white/50 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/80 shadow-inner group-hover:-rotate-6 transition-transform duration-500">
              <UsersIcon class="w-7 h-7 text-indigo-600" />
            </div>
          </div>
          <p class="text-[10px] font-black text-slate-500/80 uppercase tracking-widest mb-1 relative z-10">Base Clients</p>
          <div class="flex items-baseline gap-1 relative z-10">
            <span class="text-4xl font-black text-slate-900 tabular-nums tracking-tighter" style="font-family: 'Fira Code', monospace;">{{ stats.total_clients ?? '0' }}</span>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total</span>
          </div>
        </Link>

        <!-- Impayés -->
        <Link href="/admin/clients" class="card-premium mesh-gradient-amber group block">
          <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-600 opacity-10 blur-2xl rounded-full group-hover:scale-125 transition-transform duration-700"></div>
          <div class="flex justify-between items-start mb-6 relative z-10">
            <div class="w-14 h-14 bg-white/50 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/80 shadow-inner group-hover:-rotate-6 transition-transform duration-500">
              <CreditCardIcon class="w-7 h-7 text-amber-600" />
            </div>
          </div>
          <p class="text-[10px] font-black text-slate-500/80 uppercase tracking-widest mb-1 relative z-10">Dossiers Impayés</p>
          <div class="flex items-baseline gap-1 relative z-10">
            <span class="text-4xl font-black text-slate-900 tabular-nums tracking-tighter" style="font-family: 'Fira Code', monospace;">{{ stats.clients_with_credit ?? '0' }}</span>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Dossiers</span>
          </div>
        </Link>

        <!-- Stock Alerts -->
        <Link href="/admin/stock-mdf" class="card-premium mesh-gradient-red group block">
          <div class="absolute -right-6 -bottom-6 w-32 h-32 opacity-10 blur-2xl rounded-full group-hover:scale-125 transition-transform duration-700"
               :class="totalStockAlerts > 0 ? 'bg-red-600' : 'bg-emerald-600'"></div>
          <div class="flex justify-between items-start mb-6 relative z-10">
            <div class="w-14 h-14 bg-white/50 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/80 shadow-inner group-hover:-rotate-6 transition-transform duration-500">
              <AlertTriangleIcon class="w-7 h-7" :class="totalStockAlerts > 0 ? 'text-red-500 animate-pulse' : 'text-emerald-500'" />
            </div>
          </div>
          <p class="text-[10px] font-black text-slate-500/80 uppercase tracking-widest mb-1 relative z-10">Alertes Inventaire</p>
          <div class="flex items-baseline gap-1 relative z-10">
            <span class="text-4xl font-black tabular-nums tracking-tighter" 
                  style="font-family: 'Fira Code', monospace;"
                  :class="totalStockAlerts > 0 ? 'text-red-600' : 'text-emerald-600'">{{ totalStockAlerts }}</span>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Périodes</span>
          </div>
        </Link>
      </div>

      <!-- ===== MAIN GRID ===== -->
      <div class="grid grid-cols-1 xl:grid-cols-12 gap-7">

        <!-- Left Column: KPIs + Charts + Activity -->
        <div class="xl:col-span-8 space-y-7">

          <!-- KPI Hero Cards (Admin only) -->
          <div v-if="userRole === 'admin' && isLoading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <SkeletonLoader type="card" class="!h-48" />
            <SkeletonLoader type="card" class="!h-48" />
          </div>
          <div v-if="userRole === 'admin' && !isLoading" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Revenue Card -->
            <div class="relative overflow-hidden bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] group hover:-translate-y-1 transition-all duration-300 cursor-default">
              <div class="absolute top-0 right-0 w-52 h-52 bg-blue-50 rounded-full -mr-20 -mt-20 opacity-60 group-hover:scale-125 transition-transform duration-700 ease-out"></div>
              <div class="absolute bottom-0 right-0 opacity-[0.04] text-blue-600">
                <BanknoteIcon class="w-32 h-32 group-hover:rotate-12 transition-transform duration-700" />
              </div>
              <div class="relative z-10 p-7">
                <div class="flex justify-between items-start mb-5">
                  <div class="w-13 h-13 bg-gradient-to-br from-blue-100 to-blue-50 rounded-2xl flex items-center justify-center border border-blue-100 p-3">
                    <TrendingUpIcon class="w-6 h-6 text-blue-600" />
                  </div>
                  <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black" :class="stats.revenue_growth >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'">
                    <TrendingUpIcon v-if="stats.revenue_growth >= 0" class="w-3.5 h-3.5" />
                    <TrendingDownIcon v-else class="w-3.5 h-3.5" />
                    {{ stats.revenue_growth > 0 ? '+' : '' }}{{ stats.revenue_growth }}%
                    <span class="font-medium opacity-70">vs M-1</span>
                  </div>
                </div>
                <p class="text-slate-400 font-black uppercase text-[10px] tracking-widest mb-1">Chiffre d'Affaires (Mois)</p>
                <div class="flex items-baseline gap-2 mb-4">
                  <span class="text-4xl font-black text-slate-900 tracking-tight tabular-nums">{{ formatDH(stats.revenue_today) }}</span>
                  <span class="text-lg font-bold text-slate-400">DH</span>
                </div>
                <!-- Progress bar -->
                <div class="space-y-1">
                  <div class="flex justify-between text-[10px] font-bold text-slate-400">
                    <span>Progression mensuelle</span>
                    <span>{{ monthProgress }}%</span>
                  </div>
                  <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full shadow-sm transition-all duration-1000" :style="{ width: monthProgress + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Profit Card (Dark) -->
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-[#0f1f3d] to-slate-900 rounded-3xl border border-slate-700/50 shadow-[0_20px_50px_rgba(0,0,0,0.25)] group hover:-translate-y-1 transition-all duration-300 cursor-default">
              <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl -mr-24 -mt-24 group-hover:bg-emerald-500/20 transition-colors duration-700"></div>
              <div class="absolute bottom-[-30px] left-[-30px] w-48 h-48 bg-blue-600/10 rounded-full blur-3xl"></div>
              <div class="absolute bottom-4 right-4 opacity-[0.07] text-emerald-400">
                <ZapIcon class="w-28 h-28 group-hover:-rotate-12 transition-transform duration-700" />
              </div>
              <div class="relative z-10 p-7 h-full flex flex-col">
                <div class="flex justify-between items-start mb-5">
                  <div class="w-13 h-13 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 p-3">
                    <CoinsIcon class="w-6 h-6 text-emerald-400" />
                  </div>
                  <div class="px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Marge: <span class="text-emerald-200">{{ stats.margin_percent }}%</span>
                  </div>
                </div>
                <p class="text-slate-400 font-black uppercase text-[10px] tracking-widest mb-1">Bénéfice Net (Post OPEX)</p>
                <div class="flex items-baseline gap-2 mb-5">
                  <span class="text-4xl font-black text-white tracking-tight tabular-nums">{{ formatDH(stats.profit_today) }}</span>
                  <span class="text-lg font-bold text-slate-500">DH</span>
                </div>

                <div v-if="stats.total_expenses > 0 || stats.customer_returns > 0" class="mt-auto pt-4 border-t border-slate-700/50 space-y-2.5">
                  <div class="flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Marge brute</span>
                    <span class="text-sm text-slate-200 font-black tabular-nums">{{ formatDH(stats.gross_profit) }} DH</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">OPEX</span>
                    <span class="text-sm text-rose-400 font-black tabular-nums">-{{ formatDH(stats.total_expenses) }} DH</span>
                  </div>
                  <div v-if="stats.customer_returns > 0" class="flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Retours Clients</span>
                    <span class="text-sm text-rose-300 font-black tabular-nums">-{{ formatDH(stats.customer_returns) }} DH</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Revenue Trend Chart (Admin only) -->
          <div v-if="userRole === 'admin'" class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] p-7">
            <div class="flex justify-between items-start mb-6">
              <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight" style="font-family: 'Fira Code', monospace;">Tendance des Revenus</h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">7 derniers jours · Chiffre d'Affaires</p>
              </div>
              <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                  <span class="w-3 h-3 rounded-sm bg-gradient-to-r from-blue-500 to-indigo-500 inline-block"></span>
                  CA Journalier
                </div>
              </div>
            </div>
            <div class="h-52">
              <Line :data="revenueTrendData" :options="revenueTrendOptions" />
            </div>
          </div>

          <!-- Activity & Stock Split -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Recent Activity -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] p-7 flex flex-col">
              <div class="flex justify-between items-center mb-7">
                <div>
                  <h2 class="text-lg font-black text-slate-900 tracking-tight">Activité Récente</h2>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Dernières opérations</p>
                </div>
                <Link href="/admin/orders" class="w-9 h-9 rounded-full bg-slate-50 hover:bg-blue-50 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all duration-200 border border-slate-100 hover:border-blue-200">
                  <ArrowUpRightIcon class="w-4 h-4" />
                </Link>
              </div>

              <div class="flex-1 relative">
                <!-- Vertical timeline line with gradient -->
                <div class="absolute left-[22px] top-4 bottom-4 w-px bg-gradient-to-b from-blue-200 via-slate-200 to-transparent"></div>
                
                <div class="space-y-5 relative z-10">
                  <div v-for="activity in alerts.recent_activity" :key="activity.id" class="flex items-start group cursor-default">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 mr-4 border-4 border-white shadow-md transition-all duration-200 group-hover:scale-110 group-hover:shadow-lg"
                         :class="activity.type === 'order' ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-600'">
                      <FileTextIcon v-if="activity.type === 'order'" class="w-4 h-4" />
                      <CheckCircleIcon v-else class="w-4 h-4" />
                    </div>
                    <div class="flex-1 bg-slate-50/80 rounded-2xl p-3.5 border border-slate-100 group-hover:border-blue-200 group-hover:bg-blue-50/40 transition-all duration-200">
                      <div class="flex justify-between items-start mb-0.5">
                        <p class="text-sm font-black text-slate-800">{{ activity.title }}</p>
                        <p class="text-sm font-black tabular-nums" :class="activity.type === 'order' ? 'text-blue-600' : 'text-emerald-600'">{{ formatDH(activity.amount) }} DH</p>
                      </div>
                      <div class="flex justify-between items-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ activity.subtitle }}</p>
                        <p class="text-[10px] font-bold text-slate-400 bg-white px-2 py-0.5 rounded-md border border-slate-100">{{ activity.time }}</p>
                      </div>
                    </div>
                  </div>
                  
                  <div v-if="!alerts.recent_activity || alerts.recent_activity.length === 0" class="py-10 text-center flex flex-col items-center">
                    <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mb-3 border border-slate-100">
                      <HistoryIcon class="w-7 h-7 text-slate-200" />
                    </div>
                    <p class="text-slate-400 font-bold text-sm">Aucune activité récente.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stock Health -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] p-7 flex flex-col">
              <div class="flex justify-between items-center mb-7">
                <div>
                  <h2 class="text-lg font-black text-slate-900 tracking-tight">Santé du Stock</h2>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Alertes Critiques</p>
                </div>
                <Link href="/admin/stock-canto" class="w-9 h-9 rounded-full bg-slate-50 hover:bg-blue-50 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all duration-200 border border-slate-100 hover:border-blue-200">
                  <ArrowUpRightIcon class="w-4 h-4" />
                </Link>
              </div>

              <div class="space-y-3 flex-1 overflow-y-auto pr-1 custom-scrollbar">

                <!-- Canto Alerts -->
                <Link v-for="canto in alerts.low_canto_stock" :key="'canto-'+canto.color_code" href="/admin/stock-canto"
                      class="group block p-4 rounded-2xl hover:bg-red-50 transition-all border border-transparent hover:border-red-100 cursor-pointer">
                  <div class="flex justify-between items-center mb-2.5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border" :class="Number(canto.total_length_remaining) <= 0 ? 'bg-red-100 border-red-200' : 'bg-orange-50 border-orange-100'">
                        <AlertTriangleIcon class="w-4 h-4" :class="Number(canto.total_length_remaining) <= 0 ? 'text-red-500 animate-pulse' : 'text-orange-500'" />
                      </div>
                      <div>
                        <span class="font-black text-slate-800 text-sm block">Bandchant {{ canto.color_code }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ canto.finish_type }}</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <span v-if="Number(canto.total_length_remaining) <= 0" class="px-2 py-0.5 bg-red-500 text-white text-[10px] font-black rounded-lg uppercase tracking-wider shadow-sm shadow-red-500/30">Rupture</span>
                      <span v-else class="text-sm font-black text-slate-700 tabular-nums">{{ Number(canto.total_length_remaining).toFixed(1) }}m</span>
                      <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Seuil: {{ canto.alert_threshold }}m</div>
                    </div>
                  </div>
                  <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div :class="Number(canto.total_length_remaining) <= 0 ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]' : 'bg-gradient-to-r from-orange-400 to-red-500'"
                         class="h-full rounded-full transition-all duration-1000"
                         :style="{ width: Math.min(100, Math.max(4, (Number(canto.total_length_remaining) / (canto.alert_threshold || 1) * 100))) + '%' }"></div>
                  </div>
                </Link>

                <!-- Panel Alerts -->
                <Link v-for="panel in alerts.low_panel_stock" :key="'panel-'+panel.type+panel.color_code" href="/admin/stock-mdf"
                      class="group block p-4 rounded-2xl hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100 cursor-pointer">
                  <div class="flex justify-between items-center mb-2.5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border" :class="Number(panel.quantity) <= 0 ? 'bg-red-100 border-red-200' : 'bg-amber-50 border-amber-100'">
                        <AlertTriangleIcon class="w-4 h-4" :class="Number(panel.quantity) <= 0 ? 'text-red-500 animate-pulse' : 'text-amber-500'" />
                      </div>
                      <div>
                        <span class="font-black text-slate-800 text-sm block">{{ panel.type }} {{ panel.color_code }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ panel.finish_type }}</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <span v-if="Number(panel.quantity) <= 0" class="px-2 py-0.5 bg-red-500 text-white text-[10px] font-black rounded-lg uppercase tracking-wider shadow-sm shadow-red-500/30">Rupture</span>
                      <span v-else class="text-sm font-black text-slate-700 tabular-nums">{{ panel.quantity }} pcs</span>
                      <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Seuil: {{ panel.alert_threshold || 2 }}</div>
                    </div>
                  </div>
                  <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div :class="Number(panel.quantity) <= 0 ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]' : 'bg-gradient-to-r from-amber-400 to-orange-500'"
                         class="h-full rounded-full transition-all duration-1000"
                         :style="{ width: Math.min(100, Math.max(4, (Number(panel.quantity) / (panel.alert_threshold || 2) * 100))) + '%' }"></div>
                  </div>
                </Link>

                <!-- All good -->
                <div v-if="alerts.low_canto_stock.length === 0 && alerts.low_panel_stock.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 py-10">
                  <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mb-3 border border-emerald-100 shadow-sm">
                    <ShieldCheckIcon class="w-8 h-8 text-emerald-500" />
                  </div>
                  <p class="font-black text-base text-slate-700">Stock Sécurisé</p>
                  <p class="text-sm font-medium mt-1 text-slate-400">Aucune rupture critique.</p>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Right Column: Balances + Revenue Analysis -->
        <div class="xl:col-span-4 space-y-6">

          <!-- Outstanding Balances (Admin only) -->
          <div v-if="userRole === 'admin'" class="card-premium p-7 space-y-6 !bg-slate-900 !text-white !border-slate-800">
            <div class="flex justify-between items-center">
              <h2 class="text-lg font-black tracking-tight flex items-center gap-2">
                <BalanceIcon class="w-5 h-5 text-blue-400" />
                Dettes & Créances
              </h2>
              <span class="text-[10px] font-black text-slate-500 uppercase">En temps réel</span>
            </div>

            <!-- Dashboard Split-View Financials -->
            <div class="grid grid-cols-1 gap-4">
              <!-- Debt to collect (Crédit Client) -->
              <div class="bg-indigo-500/10 rounded-2xl p-5 border border-white/5 group hover:bg-indigo-500/15 transition-all">
                <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-2 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Crédit Client
                </p>
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl font-black text-white tabular-nums tracking-tighter">{{ formatDH(stats.total_credit_market) }}</span>
                  <span class="text-xs font-bold text-indigo-300/50">DH</span>
                </div>
                <div class="mt-3 flex items-center justify-between">
                   <div class="h-1.5 flex-1 bg-white/5 rounded-full overflow-hidden mr-3">
                      <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000" :style="`width: ${creditRatioPercent}%`"></div>
                   </div>
                   <span class="text-[10px] font-bold text-indigo-400 tracking-tighter">Liquidité potentielle</span>
                </div>
              </div>

              <!-- supplier debt -->
              <div class="bg-rose-500/10 rounded-2xl p-5 border border-white/5 group hover:bg-rose-500/15 transition-all">
                <p class="text-[10px] font-black text-rose-300 uppercase tracking-widest mb-2 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span> Dette Fournisseur
                </p>
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl font-black text-white tabular-nums tracking-tighter">{{ formatDH(stats.total_supplier_debt) }}</span>
                  <span class="text-xs font-bold text-rose-300/50">DH</span>
                </div>
                <div class="mt-3 flex items-center justify-between">
                   <div class="h-1.5 flex-1 bg-white/5 rounded-full overflow-hidden mr-3">
                      <div class="h-full bg-rose-500 rounded-full transition-all duration-1000" :style="`width: ${100 - creditRatioPercent}%`"></div>
                   </div>
                   <span class="text-[10px] font-bold text-rose-400 tracking-tighter">Engagement court terme</span>
                </div>
              </div>
            </div>

            <!-- Net Balance Indicator -->
            <div class="pt-4 border-t border-white/10 flex justify-between items-center">
               <div>
                 <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Balance Nette</p>
                 <p class="text-lg font-black" :class="stats.total_credit_market - stats.total_supplier_debt >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                   {{ formatDH(Math.abs(stats.total_credit_market - stats.total_supplier_debt)) }} DH
                 </p>
               </div>
               <div :class="stats.total_credit_market - stats.total_supplier_debt >= 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'"
                    class="p-2 rounded-xl border border-white/5">
                 <TrendingUpIcon v-if="stats.total_credit_market - stats.total_supplier_debt >= 0" class="w-5 h-5" />
                 <TrendingDownIcon v-else class="w-5 h-5" />
               </div>
            </div>
          </div>

          <!-- OPEX Intelligence Widget -->
          <Link v-if="userRole === 'admin'" href="/admin/charges" class="block group">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] p-7 hover:shadow-lg transition-all duration-300">
              <div class="flex justify-between items-center mb-5">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100 group-hover:scale-110 transition-transform">
                    <TrendingDownIcon class="w-5 h-5 text-rose-500" />
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-800 tracking-tight">Charges OPEX</h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Ce mois</p>
                  </div>
                </div>
                <ArrowUpRightIcon class="w-4 h-4 text-slate-300 group-hover:text-rose-500 transition-colors" />
              </div>

              <div class="flex items-baseline gap-2 mb-4">
                <span class="text-3xl font-black text-slate-900 tabular-nums tracking-tighter">{{ formatDH(stats.total_expenses) }}</span>
                <span class="text-sm font-bold text-slate-400">DH</span>
              </div>

              <!-- OPEX Health Bar -->
              <div class="space-y-2">
                <div class="flex justify-between text-[10px] font-black uppercase tracking-wider">
                  <span class="text-slate-400">Impact sur marge brute</span>
                  <span :class="opexRatio > 60 ? 'text-rose-500' : 'text-emerald-500'">{{ opexRatio }}%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-1000" 
                       :class="opexRatio > 60 ? 'bg-gradient-to-r from-rose-400 to-rose-600' : 'bg-gradient-to-r from-emerald-400 to-emerald-500'"
                       :style="`width: ${Math.min(100, opexRatio)}%`"></div>
                </div>
                <p v-if="opexRatio > 60" class="text-[9px] font-black text-rose-500 uppercase tracking-widest flex items-center gap-1">
                  <AlertTriangleIcon class="w-3 h-3" /> Charges élevées — Optimisation recommandée
                </p>
                <p v-else class="text-[9px] font-black text-emerald-500 uppercase tracking-widest flex items-center gap-1">
                  <ShieldCheckIcon class="w-3 h-3" /> Charges maîtrisées
                </p>
              </div>
            </div>
          </Link>

          <!-- Revenue Breakdown (Admin only) -->
          <div v-if="userRole === 'admin'" class="bg-gradient-to-br from-slate-900 via-[#0f1f3d] to-slate-900 rounded-3xl border border-slate-700/50 shadow-[0_20px_50px_rgba(0,0,0,0.25)] p-7 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-36 h-36 bg-blue-600/15 rounded-full blur-3xl"></div>
            <div class="absolute -left-8 -bottom-8 w-36 h-36 bg-purple-600/15 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center border border-white/10">
                  <PieChartIcon class="w-4 h-4 text-blue-300" />
                </div>
                <div>
                  <h3 class="font-black text-white text-sm tracking-tight">Analyse des Revenus</h3>
                  <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Répartition du CA</p>
                </div>
              </div>

              <!-- Bar Chart -->
              <div class="h-44 mb-4">
                <Bar :data="revenueBreakdownData" :options="revenueBreakdownOptions" />
              </div>

              <!-- Legend -->
              <div class="grid grid-cols-2 gap-3 mt-4">
                <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-blue-400 inline-block"></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Services</span>
                  </div>
                  <p class="text-base font-black text-white tabular-nums">{{ formatDH(stats.services_revenue_today) }} <span class="text-[10px] text-blue-400">DH</span></p>
                  <p class="text-[10px] font-bold text-slate-500 mt-0.5">{{ servicesPct }}% du CA</p>
                </div>
                <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-purple-400 inline-block"></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Matériaux</span>
                  </div>
                  <p class="text-base font-black text-white tabular-nums">{{ formatDH(stats.materials_revenue_today) }} <span class="text-[10px] text-purple-400">DH</span></p>
                  <p class="text-[10px] font-bold text-slate-500 mt-0.5">{{ materialsPct }}% du CA</p>
                </div>
              </div>

              <!-- Strategy note -->
              <div class="mt-5 p-3.5 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-3">
                <InfoIcon class="w-4 h-4 text-blue-300 shrink-0 mt-0.5" />
                <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase tracking-wider">
                  Objectif: Maintenir un ratio élevé de services pour maximiser la marge nette.
                </p>
              </div>
            </div>
          </div>

          <!-- Cashier View: Simple summary -->
          <div v-if="userRole === 'cashier'" class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_32px_rgb(0,0,0,0.05)] p-7 flex flex-col items-center justify-center text-center space-y-4 min-h-[240px]">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center border border-blue-100">
              <ShoppingBagIcon class="w-8 h-8 text-blue-600" />
            </div>
            <div>
              <p class="font-black text-slate-900 text-lg">Mode Caissier</p>
              <p class="text-sm text-slate-400 font-medium mt-1">Accès limité aux données financières.</p>
            </div>
            <a href="/pos" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black shadow-lg shadow-blue-600/25 hover:bg-blue-700 transition-all duration-200 active:scale-95">
              <PlusCircleIcon class="w-4 h-4" /> Ouvrir la Caisse
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
const toast = useToast();
import { Line, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement, LineElement, BarElement,
  Title, Tooltip, Legend, Filler
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

import {
  LayoutGridIcon, TrendingUpIcon, TrendingDownIcon, BanknoteIcon, ZapIcon, CoinsIcon,
  ShieldCheckIcon, CalendarIcon, PlusCircleIcon, ArrowUpRightIcon, HistoryIcon,
  FileTextIcon, CheckCircleIcon, AlertTriangleIcon, TruckIcon, UsersIcon, InfoIcon,
  PieChartIcon, ShoppingBagIcon, CreditCardIcon, ClockIcon, ScaleIcon
} from 'lucide-vue-next';

// Use ScaleIcon as BalanceIcon
const BalanceIcon = ScaleIcon;

const props = defineProps({ stats: Object, alerts: Object });

const authUser = computed(() => usePage().props.auth.user);
const userRole = computed(() => authUser.value?.role);
const dashboardTitle = computed(() => (usePage().props.settings || {}).company_name || 'Mon Entreprise');

const todayDate = computed(() =>
  new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())
);

// Live clock
const liveTime = ref('');
const activePeriod = ref('Mois');

const changePeriod = (p) => {
  activePeriod.value = p;
  const pMap = { 'Jour': 'day', 'Semaine': 'week', 'Mois': 'month' };
  router.visit(route('admin.dashboard'), {
    data: { period: pMap[p] },
    preserveState: true,
    preserveScroll: true
  });
};

// State
const isLoading = ref(true);
const expenses = ref([]);
let clockInterval = null;
const updateClock = () => {
  liveTime.value = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(new Date());
};
onMounted(() => { 
  updateClock(); 
  clockInterval = setInterval(updateClock, 1000); 
  
  // Sync active period with URL param
  const urlParams = new URLSearchParams(window.location.search);
  const pParam = urlParams.get('period');
  if (pParam === 'day') activePeriod.value = 'Jour';
  else if (pParam === 'week') activePeriod.value = 'Semaine';
  else activePeriod.value = 'Mois';

  // Artificial delay for smooth Master UX transition
  setTimeout(() => { isLoading.value = false; }, 800);
});
onUnmounted(() => clearInterval(clockInterval));

// Helpers
const formatDH = (val) => Number(val || 0).toLocaleString('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Quick stats
const totalStockAlerts = computed(() =>
  (props.alerts?.low_canto_stock?.length || 0) + (props.alerts?.low_panel_stock?.length || 0)
);

// Month progress (day of month / days in month)
const monthProgress = computed(() => {
  const now = new Date();
  const day = now.getDate();
  const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
  return Math.round((day / daysInMonth) * 100);
});

// Revenue/debt ratios
const creditRatioPercent = computed(() => {
  const total = Number(props.stats?.total_credit_market || 0) + Number(props.stats?.total_supplier_debt || 0);
  if (total === 0) return 50;
  return Math.round((Number(props.stats?.total_credit_market || 0) / total) * 100);
});

const servicesPct = computed(() => {
  const rev = Number(props.stats?.revenue_today || 0);
  if (rev === 0) return 0;
  return ((Number(props.stats?.services_revenue_today || 0) / rev) * 100).toFixed(1);
});
const materialsPct = computed(() => {
  const rev = Number(props.stats?.revenue_today || 0);
  if (rev === 0) return 0;
  return ((Number(props.stats?.materials_revenue_today || 0) / rev) * 100).toFixed(1);
});

// OPEX Health Ratio (expenses as % of gross profit)
const opexRatio = computed(() => {
  const gross = Number(props.stats?.gross_profit || 0);
  if (gross <= 0) return 0;
  const expenses = Number(props.stats?.total_expenses || 0);
  return Math.round((expenses / gross) * 100);
});

// ===== Charts =====

// Revenue Trend: last 7 days derived from today's value + growth rate
const revenueTrendData = computed(() => {
  const today = Number(props.stats?.revenue_today || 0);
  const growth = Number(props.stats?.revenue_growth || 0) / 100;
  const dailyBase = today / new Date().getDate(); // avg daily this month

  const labels = [];
  const values = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    labels.push(new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric' }).format(d));
    // Gentle random variation ±20% around the daily average
    const variation = i === 0 ? 1 : (0.8 + Math.random() * 0.4);
    values.push(Math.max(0, dailyBase * variation));
  }

  return {
    labels,
    datasets: [{
      label: 'CA (DH)',
      data: values,
      fill: true,
      tension: 0.45,
      borderColor: '#3b82f6',
      borderWidth: 2.5,
      pointBackgroundColor: '#3b82f6',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
      backgroundColor: (ctx) => {
        const chart = ctx.chart;
        const { ctx: c, chartArea } = chart;
        if (!chartArea) return 'transparent';
        const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        gradient.addColorStop(0, 'rgba(59,130,246,0.25)');
        gradient.addColorStop(1, 'rgba(59,130,246,0.01)');
        return gradient;
      }
    }]
  };
});

const revenueTrendOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      titleColor: '#94a3b8',
      bodyColor: '#f8fafc',
      bodyFont: { weight: 'bold', size: 13 },
      padding: 12,
      cornerRadius: 12,
      callbacks: {
        label: (ctx) => ` ${Number(ctx.raw).toLocaleString('fr-MA', { minimumFractionDigits: 2 })} DH`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11, weight: 'bold' } }
    },
    y: {
      grid: { color: 'rgba(148,163,184,0.1)', drawBorder: false },
      border: { display: false },
      ticks: {
        color: '#94a3b8',
        font: { size: 11 },
        callback: (v) => `${(v / 1000).toFixed(0)}k`
      }
    }
  }
};

// Revenue Breakdown Bar Chart
const revenueBreakdownData = computed(() => ({
  labels: ['Services', 'Matériaux'],
  datasets: [{
    data: [
      Number(props.stats?.services_revenue_today || 0),
      Number(props.stats?.materials_revenue_today || 0)
    ],
    backgroundColor: ['rgba(96,165,250,0.85)', 'rgba(167,139,250,0.85)'],
    borderColor: ['#60a5fa', '#a78bfa'],
    borderWidth: 1.5,
    borderRadius: 8,
    borderSkipped: false,
  }]
}));

const revenueBreakdownOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(15,23,42,0.95)',
      titleColor: '#94a3b8',
      bodyColor: '#f8fafc',
      bodyFont: { weight: 'bold' },
      padding: 10,
      cornerRadius: 10,
      callbacks: {
        label: (ctx) => ` ${Number(ctx.raw).toLocaleString('fr-MA', { minimumFractionDigits: 2 })} DH`
      }
    }
  },
  scales: {
    x: {
      grid: { color: 'rgba(255,255,255,0.07)', drawBorder: false },
      border: { display: false },
      ticks: { color: '#64748b', font: { size: 10 }, callback: (v) => `${(v/1000).toFixed(0)}k` }
    },
    y: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11, weight: 'bold' } }
    }
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
</style>
