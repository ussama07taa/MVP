<template>
  <div class="max-w-7xl mx-auto pb-16 px-4 sm:px-6 lg:px-8 space-y-8 mt-6">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:justify-between md:items-end gap-6 relative z-10">
      <div>
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-slate-900/5 border border-slate-900/10 mb-4 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
          <span class="text-xs font-bold text-slate-700 tracking-wider uppercase">OPEX & Trésorerie</span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight">
          Charges <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-500 to-orange-500">& Dépenses</span>
        </h1>
        <p class="text-slate-500 text-base mt-3 max-w-2xl mx-4 sm:mx-auto leading-relaxed">
          Gérez vos charges opérationnelles (loyer, salaires, abonnements) et variables (réparations, transport) pour un calcul de rentabilité exact.
        </p>
      </div>
      <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto mt-6 md:mt-0">
        <!-- GLOBAL MONTH SELECTOR -->
        <div class="relative group w-full sm:w-auto min-w-[180px]">
          <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-rose-500 z-10" />
          <input type="month" v-model="selectedMonth" 
            class="w-full h-12 pl-12 pr-4 bg-slate-900 text-white border-none rounded-2xl text-xs font-black focus:ring-4 focus:ring-rose-500/20 cursor-pointer shadow-lg hover:bg-slate-800 transition-all uppercase tracking-widest">
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Actualiser Button -->
            <button @click="loadExpenses" 
              :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
              class="btn-secondary !p-3"
              title="Actualiser">
              <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 transition-transform duration-500" />
            </button>

            <button @click="exportData('expenses')" class="btn-secondary !p-3 font-bold !text-emerald-600" title="Exporter Excel">
              <FileDownIcon class="w-5 h-5" />
            </button>

            <button @click="showForm = !showForm" class="btn-primary flex-1 md:flex-none">
              <PlusIcon class="w-5 h-5 mr-2 transition-transform duration-300" :class="{'rotate-45': showForm}"/> 
              <span>{{ showForm ? 'Fermer' : 'Nouvelle Dépense' }}</span>
            </button>
        </div>
      </div>
    </header>

    <!-- PRIMARY FINANCIAL CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <!-- Charges du Mois -->
      <div class="card-premium p-6 overflow-hidden group hover:shadow-premium transition-all duration-300">
        <div class="relative z-10">
          <div class="flex items-center space-x-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 border border-rose-100">
              <TrendingDownIcon class="w-5 h-5"/>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Charges (Ce Mois)</p>
          </div>
          <p class="text-3xl font-black text-slate-900 tracking-tight">{{ formatMoney(stats.total_this_month) }} <span class="text-sm text-slate-400">DH</span></p>
          <div class="flex items-center mt-3 text-xs font-black uppercase tracking-wider" :class="stats.trend > 0 ? 'text-rose-500' : (stats.trend < 0 ? 'text-emerald-500' : 'text-slate-400')">
            <TrendingUpIcon v-if="stats.trend > 0" class="w-3.5 h-3.5 mr-1"/>
            <TrendingDownIcon v-else-if="stats.trend < 0" class="w-3.5 h-3.5 mr-1"/>
            <span>{{ stats.trend > 0 ? '+' : '' }}{{ stats.trend }}% vs mois dernier</span>
          </div>
        </div>
      </div>

      <!-- Revenu Total -->
      <div class="card-premium p-6 overflow-hidden group hover:shadow-premium transition-all duration-300">
        <div class="relative z-10">
          <div class="flex items-center space-x-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 border border-blue-100">
              <CircleDollarSignIcon class="w-5 h-5"/>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenu Total</p>
          </div>
          <p class="text-3xl font-black text-slate-900 tracking-tight">{{ formatMoney(stats.total_revenue) }} <span class="text-sm text-slate-400">DH</span></p>
          <p class="mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Factures + Ventes POS</p>
        </div>
      </div>

      <!-- BÉNÉFICE NET (Le Juge de Paix) -->
      <div class="relative rounded-3xl p-6 overflow-hidden group transition-all duration-300 border"
           :class="stats.net_profit >= 0 
             ? 'bg-gradient-to-br from-slate-900 to-slate-800 border-slate-700 shadow-[0_8px_30px_rgb(0,0,0,0.15)]' 
             : 'bg-gradient-to-br from-rose-900 to-rose-800 border-rose-700 shadow-[0_8px_30px_rgb(200,0,0,0.15)]'">
        <div class="relative z-10">
          <div class="flex items-center space-x-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg"
                 :class="stats.net_profit >= 0 ? 'bg-emerald-500 text-white' : 'bg-white text-rose-600'">
              <ActivityIcon class="w-5 h-5"/>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest" :class="stats.net_profit >= 0 ? 'text-slate-400' : 'text-rose-300'">Bénéfice Net</p>
          </div>
          <p class="text-3xl font-black tracking-tight" :class="stats.net_profit >= 0 ? 'text-white' : 'text-white'">{{ formatMoney(stats.net_profit) }} <span class="text-sm opacity-50">DH</span></p>
          <div class="flex items-center gap-2 mt-3">
            <div class="h-1.5 flex-1 bg-white/10 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-1000"
                   :class="stats.net_profit >= 0 ? 'bg-emerald-400' : 'bg-rose-400'"
                   :style="`width: ${stats.total_revenue > 0 ? Math.max(0, Math.min(100, (stats.net_profit / stats.total_revenue) * 100)) : 0}%`"></div>
            </div>
            <span class="text-[10px] font-black opacity-50 text-white">{{ stats.total_revenue > 0 ? Math.round((stats.net_profit / stats.total_revenue) * 100) : 0 }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- SECONDARY STATS ROW -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Annuel -->
      <div class="card-premium p-5 flex items-center gap-4 group hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 border border-emerald-100">
          <CalendarDaysIcon class="w-5 h-5"/>
        </div>
        <div>
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Annuel ({{ selectedMonth?.split('-')[0] }})</p>
          <p class="text-lg font-black text-slate-800 tracking-tight">{{ formatMoney(stats.total_year) }} <span class="text-[9px] text-slate-400">DH</span></p>
        </div>
      </div>
      <!-- Fixes -->
      <div class="card-premium p-5 flex items-center gap-4 group hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 border border-blue-100">
          <WalletIcon class="w-5 h-5"/>
        </div>
        <div>
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Charges Fixes</p>
          <p class="text-lg font-black text-slate-800 tracking-tight">{{ formatMoney(stats.total_fixed) }} <span class="text-[9px] text-slate-400">DH</span></p>
        </div>
      </div>
      <!-- Variables -->
      <div class="card-premium p-5 flex items-center gap-4 group hover:shadow-sm transition-all">
        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 border border-amber-100">
          <ActivityIcon class="w-5 h-5"/>
        </div>
        <div>
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Charges Variables</p>
          <p class="text-lg font-black text-slate-800 tracking-tight">{{ formatMoney(stats.total_variable) }} <span class="text-[9px] text-slate-400">DH</span></p>
        </div>
      </div>
    </div>

    <!-- ANALYTICS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- History Trend Chart -->
        <div class="lg:col-span-8 bg-white rounded-3xl p-8 border border-slate-100 shadow-md h-full min-h-[400px] flex flex-col">
            <div class="flex justify-between items-center mb-10">
                <div>
                   <h3 class="text-lg font-black text-slate-800 tracking-tight uppercase tracking-widest flex items-center gap-3">
                     <TrendingUpIcon class="w-5 h-5 text-rose-500" />
                     Analyse des Tendances
                   </h3>
                   <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Evolution mensuelle sur les 12 derniers mois</p>
                </div>
                <div class="h-10 px-4 bg-slate-50 border border-slate-100 rounded-xl flex items-center">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Somme Mensuelle (DH)</span>
                </div>
            </div>
            
            <div class="flex-1 min-h-[250px] relative">
              <Line :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="lg:col-span-4 bg-white rounded-3xl p-8 border border-slate-100 shadow-md h-full flex flex-col">
            <div class="mb-10">
               <h3 class="text-lg font-black text-slate-800 tracking-tight uppercase tracking-widest flex items-center gap-3">
                 <PieChartIcon class="w-5 h-5 text-amber-500" />
                 Répartition
               </h3>
               <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Détails des catégories ce mois</p>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center relative min-h-[200px]">
                <Doughnut v-if="categoryChartData.datasets[0].data.length > 0" :data="categoryChartData" :options="categoryChartOptions" />
                <div v-else class="text-center py-10 opacity-30">
                    <RotateCwIcon class="w-12 h-12 mx-auto mb-4 animate-spin" />
                    <p class="text-xs font-bold uppercase">Analyse en cours...</p>
                </div>
            </div>

            <div class="mt-8 space-y-2 max-h-[140px] overflow-y-auto custom-scrollbar pr-2">
                <div v-for="(cat, idx) in categoriesData" :key="idx" class="flex items-center justify-between group">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: getChartColor(idx) }"></div>
                        <span class="text-[10px] font-black text-slate-600 truncate uppercase">{{ cat.category }}</span>
                    </div>
                    <span class="text-[10px] font-black text-slate-400">{{ formatMoney(cat.total) }} DH</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Form (HIGH-PERFORMANCE MASTER CINEMA-GLASS MODAL) -->
    <TransitionRoot as="template" :show="showForm">
      <Dialog as="div" class="relative z-[100]" @close="showForm = false">
        <!-- Backdrop: Standard Blur for High Performance -->
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-[101] overflow-y-auto overflow-x-hidden">
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95 translate-y-4" enter-to="opacity-100 scale-100 translate-y-0" leave="ease-in duration-200" leave-from="opacity-100 scale-100 translate-y-0" leave-to="opacity-0 scale-95 translate-y-4">
              <DialogPanel class="relative transform overflow-hidden rounded-[2rem] bg-white/95 backdrop-blur-sm text-left shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] transition-all w-full max-w-2xl border border-slate-200">
                
                <div class="relative px-8 py-10 sm:px-12 sm:pb-12">
                  <!-- Header: Clear & Direct -->
                  <div class="flex items-start justify-between mb-10">
                    <div>
                      <DialogTitle class="text-4xl font-black text-slate-900 tracking-tighter leading-tight">
                        Nouvelle <span class="text-transparent bg-clip-text bg-gradient-to-br from-rose-500 to-orange-600">Dépense</span>
                      </DialogTitle>
                      <p class="mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">Enregistrement financier sécurisé</p>
                    </div>
                    <button @click="showForm = false" class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 hover:text-rose-500 transition-all">
                      <XIcon class="w-6 h-6" />
                    </button>
                  </div>

                  <div class="space-y-8">
                    <!-- Section 1: Essentials -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <label class="flex items-center text-[10px] font-black text-slate-500 uppercase tracking-widest px-1">
                                <ReceiptIcon class="w-4 h-4 mr-2 text-rose-500" />
                                Détails & Date
                            </label>
                            <div class="space-y-4">
                                <input type="text" v-model="form.title" placeholder="Titre de la dépense..." 
                                    class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-slate-800 font-bold px-5 bg-slate-50/50">
                                <input type="date" v-model="form.expense_date" 
                                    class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-slate-800 font-bold px-5 bg-slate-50/50 uppercase">
                            </div>
                        </div>

                        <div class="space-y-6">
                            <label class="flex items-center text-[10px] font-black text-slate-500 uppercase tracking-widest px-1">
                                <CircleDollarSignIcon class="w-4 h-4 mr-2 text-rose-500" />
                                Montant & Catégorie
                            </label>
                            <div class="space-y-4">
                                <div class="relative">
                                    <input type="number" v-model="form.amount" placeholder="0.00" 
                                        class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-rose-600 font-black text-xl px-5 pr-14">
                                    <span class="absolute inset-y-0 right-6 flex items-center text-slate-300 font-black text-xs">DH</span>
                                </div>
                                <div class="relative">
                                  <transition name="fade" mode="out-in">
                                    <div v-if="!isAddingNewCategory" class="flex gap-2">
                                      <select v-model="form.category" class="flex-1 h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-slate-800 font-bold px-5 bg-slate-50/50">
                                        <optgroup v-for="(cats, groupName) in availableCategories" :key="groupName" :label="groupName">
                                          <option v-for="cat in cats" :key="cat" :value="cat">{{ cat }}</option>
                                        </optgroup>
                                        <optgroup v-if="customCategories.length" label="Perso">
                                          <option v-for="cat in customCategories" :key="cat" :value="cat">{{ cat }}</option>
                                        </optgroup>
                                      </select>
                                      <button @click="isAddingNewCategory = true; form.category = ''" 
                                        class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center hover:bg-rose-500 transition-colors shadow-sm">
                                        <PlusIcon class="w-6 h-6"/>
                                      </button>
                                    </div>
                                    <div v-else class="flex gap-2">
                                      <input type="text" v-model="form.category" placeholder="Nouvelle..." ref="newCatInput"
                                        class="flex-1 h-14 rounded-2xl border-rose-200 bg-rose-50 text-rose-900 font-black px-5">
                                      <button @click="isAddingNewCategory = false; form.category = availableCategories[0]" 
                                        class="w-14 h-14 rounded-2xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:text-rose-500 transition-all">
                                        <XIcon class="w-6 h-6"/>
                                      </button>
                                    </div>
                                  </transition>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recurring -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center">
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="form.is_recurring" class="sr-only">
                                <div class="block w-12 h-7 bg-slate-200 rounded-full transition-all group-hover:bg-slate-300" :class="{'bg-emerald-500': form.is_recurring}"></div>
                                <div class="absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform shadow-sm" :class="{'translate-x-5': form.is_recurring}"></div>
                            </div>
                            <div class="ml-4">
                                <span class="block text-sm font-black text-slate-700">Dépense récurrente</span>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest">Générer chaque mois</span>
                            </div>
                        </label>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-6">
                        <label class="flex items-center text-[10px] font-black text-slate-500 uppercase tracking-widest px-1">
                            <FileTextIcon class="w-4 h-4 mr-2 text-emerald-500" />
                            Notes & Justificatif
                        </label>
                        <textarea v-model="form.notes" rows="2" placeholder="Notes pour l'audit..."
                            class="w-full rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 text-slate-800 font-medium p-5 bg-slate-50/50"></textarea>
                        
                        <div class="border-2 border-dashed rounded-3xl p-8 flex flex-col items-center justify-center transition-all bg-slate-50/50" 
                             :class="form.attachment ? 'border-emerald-500 bg-emerald-50/10' : 'border-slate-200 hover:border-emerald-400 cursor-pointer'"
                             @click="!form.attachment && triggerFileInput()">
                            
                            <input type="file" id="expense_file" class="hidden" @change="handleFileUpload" accept="image/*,.pdf">
                            
                            <div v-if="!form.attachment" class="flex items-center space-x-6">
                                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-slate-300 shadow-sm">
                                    <PlusCircleIcon class="w-8 h-8" />
                                </div>
                                <div class="text-left">
                                    <p class="text-xs font-black text-slate-700">Scan du reçu / Facture (Max 5MB)</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">PDF ou Image</p>
                                </div>
                            </div>

                            <div v-else class="w-full flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-emerald-50">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                                        <component :is="getFileIconComponent(form.attachment.name)" class="w-6 h-6" />
                                    </div>
                                    <div class="text-left">
                                        <p class="text-xs font-black text-slate-800 truncate max-w-[240px]">{{ form.attachment.name }}</p>
                                        <p class="text-[9px] font-bold text-emerald-500 uppercase">{{ (form.attachment.size / 1024 / 1024).toFixed(2) }} MB</p>
                                    </div>
                                </div>
                                <button type="button" @click.stop="form.attachment = null" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                    <XIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="mt-12 flex gap-4">
                    <button @click="showForm = false" class="flex-1 h-16 rounded-2xl text-xs font-black text-slate-400 hover:bg-slate-50 transition-all uppercase tracking-widest">
                        Annuler
                    </button>
                    <button @click="saveExpense" :disabled="isSaving" 
                        class="flex-[2] h-16 rounded-2xl bg-slate-900 text-white font-black shadow-xl transition-all hover:bg-slate-800 active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3">
                        <ReceiptIcon class="w-6 h-6 text-rose-500" />
                        <span class="tracking-widest uppercase text-xs">{{ isSaving ? 'SYNCHRONISATION...' : 'VALIDER LA DÉPENSE' }}</span>
                    </button>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
      <div class="p-6 sm:px-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h2 class="text-lg font-black text-slate-800 flex items-center">
          Historique des Transactions
          <span class="ml-3 text-xs font-bold px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">{{ filteredExpenses.length }} Enregistrements</span>
        </h2>
        
        <!-- FILTERS -->
        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <SearchIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input type="text" v-model="searchQuery" placeholder="Rechercher dépense..." 
                  class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-rose-500 outline-none transition-all">
            </div>

            <div class="flex bg-slate-100 p-1 rounded-xl w-full md:w-auto">
                <button @click="activeFilter = 'ALL'" :class="{'bg-white shadow-sm text-slate-800': activeFilter === 'ALL', 'text-slate-500 hover:text-slate-700': activeFilter !== 'ALL'}" class="flex-1 px-4 py-1.5 rounded-lg text-xs font-bold transition-all">
                    Toutes
                </button>
                <button @click="activeFilter = 'FIXED'" :class="{'bg-white shadow-sm text-slate-800': activeFilter === 'FIXED', 'text-slate-500 hover:text-slate-700': activeFilter !== 'FIXED'}" class="flex-1 px-4 py-1.5 rounded-lg text-xs font-bold transition-all">
                    Fixes
                </button>
                <button @click="activeFilter = 'VARIABLE'" :class="{'bg-white shadow-sm text-slate-800': activeFilter === 'VARIABLE', 'text-slate-500 hover:text-slate-700': activeFilter !== 'VARIABLE'}" class="flex-1 px-4 py-1.5 rounded-lg text-xs font-bold transition-all">
                    Variables
                </button>
            </div>
        </div>
      </div>
      
      <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
        <table class="min-w-full">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest w-40">Date</th>
              <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
              <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Catégorie</th>
              <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Montant</th>
              <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest w-24">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="exp in filteredExpenses" :key="exp.id" class="hover:bg-slate-50/80 transition-colors group">
              <td class="px-8 py-5 whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-700">{{ new Date(exp.expense_date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) }}</span>
                  <span class="text-xs font-medium text-slate-400">{{ new Date(exp.expense_date).getFullYear() }}</span>
                </div>
              </td>
              <td class="px-8 py-5">
                <div class="flex flex-col">
                  <span class="text-base font-bold text-slate-900">{{ exp.title }} <RotateCwIcon v-if="exp.is_recurring" class="inline-block w-3 h-3 text-emerald-500 ml-1" title="Récurrente" /></span>
                  <span v-if="exp.notes" class="text-xs font-medium text-slate-400 truncate max-w-xs mt-0.5">{{ exp.notes }}</span>
                </div>
              </td>
              <td class="px-8 py-5 whitespace-nowrap">
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold border" :class="getCategoryClasses(exp.category)">
                  {{ exp.category }}
                </span>
              </td>
              <td class="px-8 py-5 whitespace-nowrap text-right">
                <span class="text-lg font-black text-rose-600">- {{ formatMoney(exp.amount) }}</span>
                <span class="text-xs font-bold text-rose-400 ml-1">DH</span>
              </td>
              <td class="px-8 py-5 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openExpenseDetails(exp)" 
                    class="w-10 h-10 inline-flex items-center justify-center rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Détails">
                    <EyeIcon class="w-5 h-5"/>
                  </button>
                  <button @click="deleteExpense(exp.id)" 
                    class="w-10 h-10 inline-flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Supprimer">
                    <Trash2Icon class="w-5 h-5"/>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredExpenses.length === 0">
              <td colspan="5" class="px-8 py-24 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-50 border border-slate-100 mb-6 text-slate-300">
                  <ReceiptIcon class="w-10 h-10"/>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Aucune dépense enregistrée</h3>
                <p class="text-sm text-slate-500">Cliquez sur "Nouvelle Dépense" pour créer votre premier OPEX.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- EXPENSE DETAIL MODAL -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-300" @click="isModalOpen = false">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden" @click.stop>
            <!-- Modal Header -->
            <div class="bg-slate-800 p-6 flex justify-between items-start">
                <div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 text-white mb-3">
                        {{ isExpenseFixed(selectedExpense) ? 'Charge Fixe' : 'Charge Variable' }}
                    </span>
                    <h3 class="text-xl font-black text-white flex items-center">
                      {{ selectedExpense.title }}
                      <RotateCwIcon v-if="selectedExpense.is_recurring" class="w-4 h-4 ml-2 text-emerald-400" title="Récurrente" />
                    </h3>
                </div>
                <button @click="isModalOpen = false" class="text-slate-400 hover:text-white transition-colors">
                    <XIcon class="w-6 h-6" />
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 space-y-6">
                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Montant</span>
                    <span class="text-2xl font-black text-rose-500">- {{ formatMoney(selectedExpense.amount) }} DH</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date</span>
                        <span class="text-sm font-bold text-slate-800">{{ new Date(selectedExpense.expense_date).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Catégorie</span>
                        <span class="text-sm font-bold text-slate-800">{{ selectedExpense.category }}</span>
                    </div>
                </div>

                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Notes & Détails</span>
                    <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-600 border border-slate-100 min-h-[80px]">
                        {{ selectedExpense.notes || 'Aucune note ajoutée pour cette dépense.' }}
                    </div>
                </div>

                <!-- ATTACHMENT SECTION -->
                <div v-if="selectedExpense?.attachment_url" class="mt-6 pt-4 border-t border-slate-100">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Pièce Jointe</span>
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 p-3 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white shadow-sm border border-slate-200 text-slate-600 rounded-lg flex items-center justify-center">
                                <FileIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800 group-hover:text-rose-600 transition-colors">
                                    Facture_Jointe.{{ selectedExpense.attachment_url.split('.').pop() }}
                                </p>
                                <p class="text-[10px] text-slate-400">Cliquez pour voir ou télécharger</p>
                            </div>
                        </div>
                        <a :href="selectedExpense.attachment_url" target="_blank" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-white hover:bg-rose-500 rounded-lg transition-all">
                            <DownloadIcon class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-end">
                <button class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 mr-2" @click="isModalOpen = false">Fermer</button>
                <button class="px-4 py-2 bg-slate-800 text-white text-sm font-bold rounded-xl hover:bg-slate-700 shadow-sm flex items-center">
                    <PrinterIcon class="w-4 h-4 mr-2" /> Reçu
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import { 
  Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild 
} from '@headlessui/vue';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  ReceiptIcon, PlusIcon, PlusCircleIcon, Trash2Icon, XIcon, EyeIcon, PrinterIcon,
  TrendingDownIcon, TrendingUpIcon, WalletIcon, ActivityIcon, RotateCwIcon,
  UploadCloudIcon, FileIcon, FileImageIcon, FileTextIcon, DownloadIcon, FileDownIcon,
  BarChart3Icon, PieChartIcon, SearchIcon, CalendarDaysIcon, CircleDollarSignIcon
} from 'lucide-vue-next';

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  CategoryScale,
  ArcElement
} from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';

ChartJS.register(
  Title, Tooltip, Legend,
  LineElement, LinearScale, PointElement,
  CategoryScale, ArcElement
);

// State
const expenses = ref([]);
const stats = ref({ total_this_month: 0, total_fixed: 0, total_variable: 0, total_year: 0, trend: 0, total_revenue: 0, net_profit: 0 });
const historyData = ref([]);
const categoriesData = ref([]);
const showForm = ref(false);
const isAddingNewCategory = ref(false);
const isSaving = ref(false);
const isLoading = ref(false);
const newCatInput = ref(null);

// 1. Filter & Modal State
const activeFilter = ref('ALL');
const searchQuery = ref('');
const selectedMonth = ref(new Date().toISOString().slice(0, 7)); // YYYY-MM
const isModalOpen = ref(false);
const selectedExpense = ref(null);

const form = ref({ 
  title: '', 
  category: '🏠 Loyer (K-ra)', 
  amount: null, 
  expense_date: new Date().toISOString().slice(0,10),
  notes: '',
  is_recurring: false,
  attachment: null
});

// File Upload Handlers
const triggerFileInput = () => {
    document.getElementById('expense_file').click();
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.attachment = file;
    }
};

const getFileIconComponent = (filename) => {
    if (!filename) return FileIcon;
    const lower = filename.toLowerCase();
    if (lower.endsWith('.pdf')) return FileTextIcon;
    if (lower.match(/\.(jpg|jpeg|png|gif)$/)) return FileImageIcon;
    return FileIcon;
};

const availableCategories = ref({
  "Charges Fixes (Opex)": [
    "🏠 Loyer (K-ra)",
    "👥 Salaires (Kheddama)",
    "🌐 Internet & Téléphone",
    "⚡ Électricité & Eau"
  ],
  "Charges Variables": [
    "🔧 Entretien & Maintenance",
    "⛽ Transport & Carburant",
    "☕ Consommables (Café, etc.)",
    "⚠️ Imprévus"
  ]
});

// Calculate unique custom categories from existing data
const customCategories = computed(() => {
  const defaultCats = Object.values(availableCategories.value).flat();
  const cats = new Set();
  expenses.value.forEach(e => {
    if (e.category && !defaultCats.includes(e.category)) cats.add(e.category);
  });
  return Array.from(cats).sort();
});

// Helper to determine if expense is FIXED
const isExpenseFixed = (exp) => {
    if (!exp) return false;
    const cat = exp.category?.toLowerCase() || '';
    return cat.includes('fixe') || cat.includes('loyer') || cat.includes('électricité') || cat.includes('eau') || cat.includes('salaire');
};

// 2. Computed Analytics (Progress Bar)
const statsPercent = computed(() => {
    const total = parseFloat(stats.value.total_this_month) || 0;
    if (total === 0) return { fixed: 0, variable: 0 };
    
    const fixed = parseFloat(stats.value.total_fixed) || 0;
    const variable = parseFloat(stats.value.total_variable) || 0;
    
    return {
        fixed: Math.round((fixed / total) * 100),
        variable: Math.round((variable / total) * 100)
    };
});

// 3. Computed Filtered Expenses
const filteredExpenses = computed(() => {
    let result = expenses.value;

    // Filter by Month
    if (selectedMonth.value) {
        result = result.filter(exp => exp.expense_date.startsWith(selectedMonth.value));
    }

    // Search Query
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(exp => 
            exp.title.toLowerCase().includes(q) || 
            exp.category.toLowerCase().includes(q) ||
            (exp.notes && exp.notes.toLowerCase().includes(q))
        );
    }

    // Filter by Type
    if (activeFilter.value !== 'ALL') {
        result = result.filter(exp => {
            const isFixed = isExpenseFixed(exp);
            if (activeFilter.value === 'FIXED') return isFixed;
            if (activeFilter.value === 'VARIABLE') return !isFixed;
            return true;
        });
    }

    return result;
});

// Modal Logic
const openExpenseDetails = (expense) => {
    selectedExpense.value = expense;
    isModalOpen.value = true;
};

// Format currency smoothly
const formatMoney = (val) => {
  if(!val) return '0.00';
  return parseFloat(val).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Colors for category badges
const getCategoryClasses = (category) => {
  const cat = category?.toLowerCase() || '';
  if (cat.includes('fixe') || cat.includes('loyer') || cat.includes('électricité') || cat.includes('eau')) {
    return 'bg-blue-50/80 text-blue-700 border-blue-200/60';
  } else if (cat.includes('salaire')) {
    return 'bg-purple-50/80 text-purple-700 border-purple-200/60';
  } else {
    return 'bg-amber-50/80 text-amber-700 border-amber-200/60';
  }
};

// Actions
const loadExpenses = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/expenses', {
        params: { month: selectedMonth.value }
    });
    expenses.value = res.data.expenses;
    stats.value = res.data.stats;
    historyData.value = res.data.history;
    categoriesData.value = res.data.categories;
  } catch (error) {
    console.error("Erreur chargement dépenses", error);
  } finally {
    isLoading.value = false;
  }
};

// Reactivate dashboard on month change
watch(selectedMonth, () => {
    loadExpenses();
});

// --- CHART COMPUTATIONS ---
const chartData = computed(() => ({
  labels: historyData.value.map(h => h.month),
  datasets: [{
    label: 'Dépenses Totales',
    data: historyData.value.map(h => h.total),
    borderColor: '#f43f5e',
    backgroundColor: 'rgba(244, 63, 94, 0.1)',
    borderWidth: 4,
    fill: true,
    tension: 0.4,
    pointRadius: 6,
    pointHoverRadius: 8,
    pointBackgroundColor: '#fff',
    pointBorderColor: '#f43f5e',
    pointBorderWidth: 3
  }]
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 12,
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 14, weight: 'black' },
      callbacks: {
        label: (context) => ` ${formatMoney(context.raw)} DH`
      }
    }
  },
  scales: {
    y: { 
      beginAtZero: true, 
      grid: { display: true, color: 'rgba(148, 163, 184, 0.1)', drawBorder: false },
      ticks: { font: { weight: 'bold', size: 10 }, color: '#94a3b8' }
    },
    x: { 
      grid: { display: false },
      ticks: { font: { weight: 'bold', size: 10 }, color: '#94a3b8' }
    }
  }
};

const categoryChartData = computed(() => {
  const labels = categoriesData.value.map(c => c.category);
  const data = categoriesData.value.map(c => c.total);
  
  return {
    labels: labels,
    datasets: [{
      data: data,
      backgroundColor: labels.map((_, i) => getChartColor(i)),
      borderWidth: 0,
      hoverOffset: 15
    }]
  };
});

const categoryChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 12,
      bodyFont: { size: 12, weight: 'black' },
      callbacks: {
        label: (context) => ` ${context.label}: ${formatMoney(context.raw)} DH`
      }
    }
  }
};

const getChartColor = (idx) => {
  const colors = [
    '#f43f5e', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', 
    '#ec4899', '#06b6d4', '#f97316', '#6366f1', '#14b8a6'
  ];
  return colors[idx % colors.length];
};

const saveExpense = async () => {
  if(!form.value.title || !form.value.amount || !form.value.category) {
    return toast.warning('Veuillez remplir le titre, la catégorie et le montant.');
  }
  
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('title', form.value.title);
    formData.append('category', form.value.category);
    formData.append('amount', form.value.amount);
    formData.append('expense_date', form.value.expense_date);
    formData.append('notes', form.value.notes || '');
    formData.append('is_recurring', form.value.is_recurring ? 1 : 0);
    
    if (form.value.attachment) {
        formData.append('attachment', form.value.attachment);
    }

    await axios.post('/api/admin/expenses', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    showForm.value = false;
    isAddingNewCategory.value = false;
    // Reset form
    form.value.title = ''; 
    form.value.amount = null;
    form.value.notes = '';
    form.value.is_recurring = false;
    form.value.attachment = null;
    await loadExpenses();
  } catch(e) { 
    toast.error('Erreur lors de l\'enregistrement de la dépense.');
    console.error(e);
  } finally {
    isSaving.value = false;
  }
};

const exportData = async (type) => {
    try {
        const res = await axios.post(`/api/export/${type}`, {}, { responseType: 'blob' });
        
        let fileName = `${type}_export_${new Date().getTime()}.xlsx`;
        const disposition = res.headers['content-disposition'] || res.headers['Content-Disposition'];
        
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) { 
                fileName = matches[1].replace(/['"]/g, '');
            }
        }

        // Force extension if missing
        if (!fileName.toLowerCase().endsWith('.xlsx')) {
            fileName += '.xlsx';
        }
        
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        
        setTimeout(() => {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 200);
    } catch (e) {
        console.error('Export Error:', e);
        toast.error('Erreur lors du téléchargement de l\'export');
    }
};

onMounted(() => loadExpenses());
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.animate-shimmer {
  animation: shimmer 3s infinite linear;
}

@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 8s infinite linear;
}
</style>
