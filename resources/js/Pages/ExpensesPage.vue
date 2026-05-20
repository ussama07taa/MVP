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
      <div class="flex items-center gap-4 w-full md:w-auto">
        <!-- Actualiser Button (Master UX) -->
        <button @click="loadExpenses" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-3.5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-rose-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-rose-600 transition-transform duration-500" />
        </button>

        <button @click="exportData('expenses')" class="p-3.5 bg-white border border-emerald-200 rounded-2xl shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-300 active:scale-90 group text-emerald-600" title="Exporter Excel">
          <FileDownIcon class="w-5 h-5 group-hover:scale-110 transition-transform" />
        </button>

        <button @click="showForm = !showForm" 
          class="flex-1 md:flex-none group relative px-6 py-3.5 bg-slate-900 text-white font-bold rounded-2xl shadow-xl shadow-slate-900/20 hover:shadow-2xl hover:shadow-slate-900/30 transition-all duration-300 active:scale-95 flex items-center overflow-hidden">
          <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-slate-800 to-slate-900 group-hover:scale-105 transition-transform duration-500"></div>
          <PlusIcon class="w-5 h-5 mr-2 relative z-10 transition-transform duration-300" :class="{'rotate-45': showForm}"/> 
          <span class="relative z-10">{{ showForm ? 'Fermer le formulaire' : 'Nouvelle Dépense' }}</span>
        </button>
      </div>
    </header>

    <!-- Stats Summary Cards (Backend Powered) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Total -->
      <div class="relative bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden group hover:shadow-[0_8px_40px_rgb(0,0,0,0.08)] transition-all duration-500">
        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500 text-rose-600">
          <TrendingDownIcon class="w-24 h-24 -mr-8 -mt-8"/>
        </div>
        <div class="relative z-10 flex flex-col h-full justify-between">
          <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-100 to-rose-50 flex items-center justify-center text-rose-500 shadow-inner">
              <ActivityIcon class="w-5 h-5"/>
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total (Ce Mois)</p>
          </div>
          <div>
            <p class="text-4xl font-black text-slate-900 tracking-tight">{{ formatMoney(stats.total_this_month) }} <span class="text-xl font-bold text-slate-400">DH</span></p>
            <div class="flex items-center mt-3 text-sm font-medium" :class="stats.trend > 0 ? 'text-rose-500' : (stats.trend < 0 ? 'text-emerald-500' : 'text-slate-400')">
              <TrendingUpIcon v-if="stats.trend > 0" class="w-4 h-4 mr-1"/>
              <TrendingDownIcon v-else-if="stats.trend < 0" class="w-4 h-4 mr-1"/>
              <span>{{ stats.trend > 0 ? '+' : '' }}{{ stats.trend }}% vs mois dernier</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Fixed -->
      <div class="relative bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-800 overflow-hidden group hover:shadow-[0_8px_40px_rgb(0,0,0,0.2)] transition-all duration-500">
        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500 text-white">
          <ReceiptIcon class="w-24 h-24 -mr-8 -mt-8"/>
        </div>
        <div class="relative z-10 flex flex-col h-full justify-between">
          <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-blue-400 border border-white/5">
              <ReceiptIcon class="w-5 h-5"/>
            </div>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-wider">Charges Fixes</p>
          </div>
          <div>
            <p class="text-4xl font-black text-white tracking-tight">{{ formatMoney(stats.total_fixed) }} <span class="text-xl font-bold text-slate-500">DH</span></p>
            <p class="mt-3 text-sm font-medium text-slate-400">Loyers, salaires, abonnements</p>
          </div>
        </div>
      </div>

      <!-- Variable -->
      <div class="relative bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden group hover:shadow-[0_8px_40px_rgb(0,0,0,0.08)] transition-all duration-500">
        <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 group-hover:scale-110 transition-all duration-500 text-amber-600">
          <WalletIcon class="w-24 h-24 -mr-8 -mt-8"/>
        </div>
        <div class="relative z-10 flex flex-col h-full justify-between">
          <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center text-amber-500 shadow-inner">
              <WalletIcon class="w-5 h-5"/>
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Charges Variables</p>
          </div>
          <div>
            <p class="text-4xl font-black text-slate-900 tracking-tight">{{ formatMoney(stats.total_variable) }} <span class="text-xl font-bold text-slate-400">DH</span></p>
            <p class="mt-3 text-sm font-medium text-slate-400">Imprévus, réparations, transport</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ANALYTICS PROGRESS BAR -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] mt-6 mb-2">
        <div class="flex justify-between items-center mb-2">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-wider">Répartition des Dépenses (Ce Mois)</h4>
            <span class="text-xs font-bold text-slate-400">Total: {{ formatMoney(stats.total_this_month) }} DH</span>
        </div>
        <div class="w-full h-3 bg-slate-100 rounded-full flex overflow-hidden">
            <!-- Fixed Expenses Bar (Slate-800) -->
            <div class="bg-slate-800 h-full transition-all duration-500" :style="{ width: statsPercent.fixed + '%' }"></div>
            <!-- Variable Expenses Bar (Amber-400) -->
            <div class="bg-amber-400 h-full transition-all duration-500" :style="{ width: statsPercent.variable + '%' }"></div>
        </div>
        <div class="flex justify-between text-[10px] font-bold mt-2 uppercase">
            <span class="text-slate-600"><span class="inline-block w-2 h-2 rounded-full bg-slate-800 mr-1"></span>Fixes: {{ statsPercent.fixed }}%</span>
            <span class="text-amber-600"><span class="inline-block w-2 h-2 rounded-full bg-amber-400 mr-1"></span>Variables: {{ statsPercent.variable }}%</span>
        </div>
    </div>

    <!-- Add Form (Animated Panel) -->
    <transition enter-active-class="transition-all ease-out duration-300" enter-from-class="opacity-0 -translate-y-8 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all ease-in duration-200" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-8 scale-95">
      <div v-if="showForm" class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] border border-slate-200 overflow-hidden relative z-20">
        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 via-orange-500 to-amber-500"></div>
        <div class="p-8 sm:p-10">
          <h3 class="text-2xl font-black text-slate-800 mb-8 flex items-center">
            <PlusCircleIcon class="w-7 h-7 mr-3 text-rose-500"/> Enregistrer une dépense
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6">
            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Titre / Motif <span class="text-rose-500">*</span></label>
              <input type="text" v-model="form.title" placeholder="Ex: Paiement Loyer, Achat fourniture..." 
                class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-all text-slate-800 font-medium px-5">
            </div>
            
            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Catégorie <span class="text-rose-500">*</span></label>
              <div class="relative">
                <transition name="fade" mode="out-in">
                  <!-- Select Mode -->
                  <div v-if="!isAddingNewCategory" class="flex gap-3 h-14">
                    <select v-model="form.category" class="flex-1 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-all text-slate-800 font-medium px-5 appearance-none">
                      <optgroup v-for="(cats, groupName) in availableCategories" :key="groupName" :label="groupName">
                        <option v-for="cat in cats" :key="cat" :value="cat">{{ cat }}</option>
                      </optgroup>
                      <optgroup v-if="customCategories.length" label="Catégories Personnalisées">
                        <option v-for="cat in customCategories" :key="cat" :value="cat">{{ cat }}</option>
                      </optgroup>
                    </select>
                    <button @click="isAddingNewCategory = true; form.category = ''" 
                      class="w-14 h-14 flex-shrink-0 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all group" title="Créer une catégorie">
                      <PlusIcon class="w-6 h-6 group-hover:scale-110 transition-transform"/>
                    </button>
                  </div>
                  <!-- Input Mode -->
                  <div v-else class="flex gap-3 h-14">
                    <input type="text" v-model="form.category" placeholder="Nouvelle catégorie..." ref="newCatInput"
                      class="flex-1 rounded-2xl border-rose-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-rose-50/30 text-rose-900 font-bold px-5 placeholder-rose-300">
                    <button @click="isAddingNewCategory = false; form.category = availableCategories[0]" 
                      class="w-14 h-14 flex-shrink-0 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-all group" title="Annuler">
                      <XIcon class="w-6 h-6 group-hover:scale-110 transition-transform"/>
                    </button>
                  </div>
                </transition>
              </div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Date de la dépense <span class="text-rose-500">*</span></label>
              <input type="date" v-model="form.expense_date" 
                class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-all text-slate-800 font-medium px-5">
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Montant (DH) <span class="text-rose-500">*</span></label>
              <div class="relative">
                <input type="number" v-model="form.amount" placeholder="0.00" 
                  class="w-full h-14 rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-all text-rose-600 font-black text-xl px-5 pr-16">
                <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                  <span class="text-slate-400 font-bold text-sm">DH</span>
                </div>
              </div>
            </div>
            
            <div class="lg:col-span-4 mt-2">
              <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Notes (Optionnel)</label>
              <textarea v-model="form.notes" rows="2" placeholder="Informations supplémentaires..."
                class="w-full rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-all text-slate-800 font-medium p-5"></textarea>
            </div>
            
            <!-- File Upload Zone -->
            <div class="lg:col-span-4 mt-2">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2.5">Facture / Reçu (Optionnel)</label>
                
                <div class="border-2 border-dashed rounded-2xl p-6 text-center transition-colors" 
                     :class="form.attachment ? 'border-rose-500 bg-rose-50' : 'border-slate-200 hover:border-rose-400 bg-slate-50/50 hover:bg-slate-50'">
                    
                    <input type="file" id="expense_file" class="hidden" @change="handleFileUpload" accept="image/*,.pdf">
                    
                    <div v-if="!form.attachment" class="flex flex-col items-center justify-center">
                        <UploadCloudIcon class="w-10 h-10 text-slate-300 mb-3" />
                        <p class="text-sm text-slate-600 font-bold mb-1">Glissez-déposez votre facture ici</p>
                        <p class="text-[10px] font-bold text-slate-400 mb-4 uppercase tracking-widest">Formats: JPG, PNG, PDF (Max: 5MB)</p>
                        <button type="button" @click="triggerFileInput" class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-colors">
                            Parcourir les fichiers
                        </button>
                    </div>

                    <!-- File Selected State -->
                    <div v-else class="flex items-center justify-between bg-white p-3 rounded-xl border border-rose-200 shadow-sm">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center">
                                <component :is="getFileIconComponent(form.attachment.name)" class="w-6 h-6" />
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-bold text-slate-800 truncate max-w-[200px]">{{ form.attachment.name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ (form.attachment.size / 1024 / 1024).toFixed(2) }} MB</p>
                            </div>
                        </div>
                        <button type="button" @click="form.attachment = null" class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center">
            <!-- SMART FEATURE: Recurring Toggle -->
            <div class="mb-6 md:mb-0">
                <label class="flex items-center cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="form.is_recurring" class="sr-only">
                        <div class="block w-14 h-8 bg-slate-200 rounded-full transition-colors duration-300" :class="{'bg-emerald-500': form.is_recurring}"></div>
                        <div class="absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform duration-300 shadow-sm" :class="{'translate-x-6': form.is_recurring}"></div>
                    </div>
                    <div class="ml-4">
                        <span class="block text-sm font-black text-slate-700 flex items-center">
                          Répéter chaque mois 
                          <RotateCwIcon class="w-4 h-4 text-emerald-500 ml-2" v-if="form.is_recurring" />
                        </span>
                        <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Idéal pour le Loyer, l'Internet et les Salaires.</span>
                    </div>
                </label>
            </div>

            <button @click="saveExpense" :disabled="isSaving" 
              class="px-8 py-4 bg-gradient-to-r from-rose-500 to-orange-500 text-white font-black rounded-2xl shadow-lg shadow-rose-500/30 hover:shadow-xl hover:shadow-rose-500/40 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-200 flex items-center disabled:opacity-50">
              <ReceiptIcon class="w-5 h-5 mr-3" />
              {{ isSaving ? 'Enregistrement...' : 'Valider la Dépense' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
      <div class="p-6 sm:px-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h2 class="text-lg font-black text-slate-800 flex items-center">
          Historique des Transactions
          <span class="ml-3 text-xs font-bold px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">{{ filteredExpenses.length }} Enregistrements</span>
        </h2>
        
        <!-- FILTERS -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <div class="flex bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
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
            <input type="month" v-model="selectedMonth" class="p-2 w-full sm:w-auto text-xs font-bold border border-slate-200 rounded-xl bg-slate-50 text-slate-600 focus:ring-2 focus:ring-orange-500 outline-none">
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
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { 
  ReceiptIcon, PlusIcon, PlusCircleIcon, Trash2Icon, XIcon, EyeIcon, PrinterIcon,
  TrendingDownIcon, TrendingUpIcon, WalletIcon, ActivityIcon, RotateCwIcon,
  UploadCloudIcon, FileIcon, FileImageIcon, FileTextIcon, DownloadIcon, FileDownIcon
} from 'lucide-vue-next';

// State
const expenses = ref([]);
const stats = ref({ total_this_month: 0, total_fixed: 0, total_variable: 0, trend: 0 });
const showForm = ref(false);
const isAddingNewCategory = ref(false);
const isSaving = ref(false);
const isLoading = ref(false);
const newCatInput = ref(null);

// 1. Filter & Modal State
const activeFilter = ref('ALL');
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
    const res = await axios.get('/api/admin/expenses');
    expenses.value = res.data.expenses;
    stats.value = res.data.stats;
  } catch (error) {
    console.error("Erreur chargement dépenses", error);
  } finally {
    isLoading.value = false;
  }
};

const saveExpense = async () => {
  if(!form.value.title || !form.value.amount || !form.value.category) {
    return alert('Veuillez remplir le titre, la catégorie et le montant.');
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
    alert('Erreur lors de l\'enregistrement de la dépense.');
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
        alert('Erreur lors du téléchargement de l\'export');
    }
};

onMounted(() => loadExpenses());
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
</style>
