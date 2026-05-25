<template>
  <div class="min-h-screen bg-[#f1f5f9] font-sans selection:bg-brand-500 selection:text-white relative overflow-x-hidden">
    
    <!-- Ambient Background Blobs (Premium Touch) -->
    <div class="fixed top-0 left-0 w-full h-96 bg-gradient-to-b from-indigo-100/50 to-transparent pointer-events-none -z-10"></div>
    <div class="absolute top-[10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-brand-400/10 blur-[120px] pointer-events-none -z-10"></div>

    <div class="max-w-[1400px] mx-auto p-6 lg:p-10 space-y-10">
      
      <!-- Top Header & Search/Filters -->
      <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 relative z-10">
        <div class="flex items-center space-x-6">
          <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-500/30 transform hover:scale-105 transition-transform duration-300">
            <ReceiptIcon class="w-8 h-8 text-white" />
          </div>
          <div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-none">
              Historique des <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">Ventes</span>
            </h1>
            <p class="text-slate-500 font-bold mt-2 text-sm max-w-lg mx-4 sm:mx-auto">Registre chronologique des commandes et paiements.</p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
          <button @click="exportData('orders')" class="px-6 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 font-bold rounded-xl transition-all border border-emerald-200 flex items-center shadow-sm h-[48px]">
            <FileDownIcon class="w-5 h-5 mr-2" /> Exporter Excel
          </button>

          <button @click="loadOrders" 
            :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
            class="group relative p-3.5 bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300 active:scale-90"
            title="Actualiser les données">
            <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-indigo-600 transition-transform duration-500" />
          </button>
        </div>
      </header>

      <!-- Stats Quick Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
        <div class="bg-white/60 backdrop-blur-md border border-white p-6 rounded-[2rem] shadow-sm flex items-center justify-between group hover:border-indigo-200 transition-all">
          <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Ventes</p>
            <p class="text-2xl font-black text-slate-900">{{ stats.totalSales.toFixed(2) }} <span class="text-xs text-slate-400 font-bold">DH</span></p>
          </div>
          <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
            <ReceiptIcon class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white/60 backdrop-blur-md border border-white p-6 rounded-[2rem] shadow-sm flex items-center justify-between group hover:border-rose-200 transition-all">
          <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Retours</p>
            <p class="text-2xl font-black text-rose-600">{{ stats.totalReturns.toFixed(2) }} <span class="text-xs text-rose-300 font-bold">DH</span></p>
          </div>
          <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
            <RotateCcwIcon class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-indigo-600 p-6 rounded-[2rem] shadow-xl shadow-indigo-500/20 flex items-center justify-between group hover:bg-indigo-700 transition-all border border-indigo-400/20">
          <div>
            <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Net (CA)</p>
            <p class="text-2xl font-black text-white">{{ (stats.totalSales - stats.totalReturns).toFixed(2) }} <span class="text-xs text-indigo-300 font-bold">DH</span></p>
          </div>
          <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-white group-hover:scale-110 transition-transform">
            <CreditCardIcon class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
          <!-- Search Bar -->
          <div class="relative w-full sm:w-72 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <SearchIcon class="w-5 h-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <input v-model="searchQuery" type="text" placeholder="Référence, Client, Montant..." 
              class="w-full pl-11 pr-4 py-3.5 bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 shadow-sm transition-all text-sm font-bold text-slate-700 placeholder-slate-400">
          </div>
          
          <!-- Filter Select -->
          <div class="relative w-full sm:w-48 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <FilterIcon class="w-4 h-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <select v-model="statusFilter" class="w-full pl-11 pr-8 py-3.5 bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl text-sm font-black text-slate-700 shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 appearance-none cursor-pointer transition-all">
              <option value="all">Tous les états</option>
              <option value="paid">Payés Uniquement</option>
              <option value="unpaid">Reste à Payer</option>
            </select>
          </div>
      </div>

      <!-- MASTER UX DATE FILTERS -->
      <div class="flex flex-wrap items-center justify-between bg-white/60 backdrop-blur-md p-2 rounded-2xl shadow-sm border border-white mb-6">
          <!-- Quick Select Badges -->
          <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100/50 rounded-xl">
              <button @click="setDateFilter('all')" 
                      :class="activeDateFilter === 'all' ? 'bg-white shadow-sm text-indigo-700 font-black' : 'text-slate-500 hover:text-slate-700 font-bold'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all active:scale-95">
                  Tout
              </button>
              <button @click="setDateFilter('today')" 
                      :class="activeDateFilter === 'today' ? 'bg-white shadow-sm text-indigo-700 font-black' : 'text-slate-500 hover:text-slate-700 font-bold'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all active:scale-95">
                  Aujourd'hui
              </button>
              <button @click="setDateFilter('yesterday')" 
                      :class="activeDateFilter === 'yesterday' ? 'bg-white shadow-sm text-indigo-700 font-black' : 'text-slate-500 hover:text-slate-700 font-bold'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all active:scale-95">
                  Hier
              </button>
              <button @click="setDateFilter('week')" 
                      :class="activeDateFilter === 'week' ? 'bg-white shadow-sm text-indigo-700 font-black' : 'text-slate-500 hover:text-slate-700 font-bold'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all active:scale-95">
                  Semaine
              </button>
              <button @click="setDateFilter('month')" 
                      :class="activeDateFilter === 'month' ? 'bg-white shadow-sm text-indigo-700 font-black' : 'text-slate-500 hover:text-slate-700 font-bold'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all active:scale-95">
                  Ce mois
              </button>
              <button @click="setDateFilter('custom')" 
                      :class="activeDateFilter === 'custom' ? 'bg-indigo-100 text-indigo-800 font-black border-indigo-200' : 'text-slate-500 hover:text-slate-700 font-bold border-transparent'" 
                      class="px-4 py-2 rounded-lg text-xs transition-all border active:scale-95">
                  <CalendarIcon class="w-3 h-3 inline mr-1" /> Personnalisé
              </button>
          </div>

          <!-- Custom Date Range Picker -->
          <div v-if="activeDateFilter === 'custom'" class="flex items-center space-x-2 p-1 mt-2 lg:mt-0 animate-in fade-in slide-in-from-right-4 duration-300">
              <div class="relative group">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 font-black uppercase">Du</span>
                  <input type="date" v-model="customStartDate" class="pl-8 pr-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 bg-white rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all shadow-sm">
              </div>
              <div class="relative group">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 font-black uppercase">Au</span>
                  <input type="date" v-model="customEndDate" class="pl-8 pr-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 bg-white rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all shadow-sm">
              </div>
          </div>

          <!-- Results Summary -->
          <div class="hidden xl:flex items-center px-5 py-2 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
              <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mr-3">Ventes affichées :</span>
              <span class="text-sm font-black text-indigo-900">{{ filteredOrders.length }}</span>
          </div>
      </div>

      <!-- Skeleton Loading State -->
      <div v-if="isLoading" class="space-y-4 animate-pulse">
        <div v-for="i in 5" :key="i" class="h-24 bg-white/50 rounded-3xl border border-slate-100"></div>
      </div>

      <!-- Orders Modern List (Cards) -->
      <div v-else class="space-y-4">
        
        <!-- Header Row for Desktop -->
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-2 text-xs font-black text-slate-400 uppercase tracking-widest">
          <div class="col-span-2">Référence</div>
          <div class="col-span-3">Client</div>
          <div class="col-span-2">Date</div>
          <div class="col-span-2 text-right">Montant</div>
          <div class="col-span-3 text-right">État & Actions</div>
        </div>

        <TransitionGroup name="list" tag="div" class="space-y-3 relative">
          <div v-for="order in filteredOrders" :key="order.id" 
            @click="openOrderDetails(order)"
            class="group bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-[0_4px_20px_rgb(0,0,0,0.02)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-indigo-200/50 hover:-translate-y-0.5 transition-all duration-300 cursor-pointer grid grid-cols-1 md:grid-cols-12 items-center gap-4 relative overflow-hidden"
            :class="order.total_refunded >= order.total_sell_price ? 'bg-rose-50/20 border-rose-100' : ''">
            
            <!-- Left Glow accent -->
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                 :class="order.total_refunded >= order.total_sell_price ? 'from-rose-400 to-rose-600 opacity-100' : ((Number(order.total_sell_price) - Number(order.amount_paid)) <= 0 ? 'from-emerald-400 to-teal-500' : 'from-amber-400 to-orange-500')"></div>

            <!-- Ref -->
            <div class="md:col-span-2 flex items-center space-x-3">
              <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                <FileTextIcon class="w-5 h-5" />
              </div>
              <div class="flex flex-col">
                <span class="font-black text-slate-900 text-lg leading-tight" :class="order.total_refunded >= order.total_sell_price ? 'line-through text-rose-300' : ''">{{ order.display_reference }}</span>
                <span v-if="order.total_refunded > 0" class="inline-flex items-center text-[9px] font-black bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded mt-1 border border-rose-100 uppercase tracking-tighter w-fit">
                  <RotateCcwIcon class="w-2.5 h-2.5 mr-1" /> RETOURNÉ
                </span>
                <span v-if="order.source === 'invoice'" class="inline-flex items-center text-[9px] font-black bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded mt-1 border border-indigo-100 uppercase tracking-tighter w-fit">
                  <FileTextIcon class="w-2.5 h-2.5 mr-1" /> FACTURE MOD.
                </span>
              </div>
            </div>

            <!-- Client -->
            <div class="md:col-span-3 flex items-center">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 border border-indigo-200/50 flex items-center justify-center mr-3 text-indigo-700 font-black text-xs shadow-inner">
                {{ order.client?.name.charAt(0) }}
              </div>
              <div class="flex flex-col">
                <span class="font-bold text-slate-700 leading-tight">{{ order.client?.name }}</span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5 md:hidden">Client</span>
              </div>
            </div>

            <!-- Date -->
            <div class="md:col-span-2 flex flex-col">
              <span class="text-sm font-bold text-slate-500 flex items-center">
                <CalendarIcon class="w-4 h-4 mr-1.5 opacity-50" />
                {{ formatDate(order.created_at) }}
              </span>
            </div>

            <!-- Total Amount -->
            <div class="md:col-span-2 flex flex-col md:text-right">
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest md:hidden mb-1">Montant</span>
              <span class="font-black text-slate-900 text-lg tracking-tight" :class="order.total_refunded >= order.total_sell_price ? 'line-through text-rose-300' : ''">{{ Number(order.total_sell_price).toFixed(2) }} <span class="text-xs text-slate-400">DH</span></span>
            </div>

            <!-- Status & Actions -->
            <div class="md:col-span-3 flex items-center justify-between md:justify-end gap-3 mt-2 md:mt-0 pt-3 md:pt-0 border-t border-slate-100 md:border-0">
              
              <!-- Badges -->
              <div class="flex-1 text-left md:text-right">
                <span v-if="Number(order.amount_paid) >= Number(order.total_sell_price)" 
                  class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-50 text-emerald-600 border border-emerald-200/50">
                  <CheckCircleIcon class="w-4 h-4 mr-1.5" /> 
                  {{ Number(order.amount_paid) > Number(order.total_sell_price) ? '+' + (Number(order.amount_paid) - Number(order.total_sell_price)).toFixed(2) + ' DH' : 'Réglé' }}
                </span>
                <span v-else class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black bg-amber-50 text-amber-600 border border-amber-200/50">
                  <ClockIcon class="w-4 h-4 mr-1.5" /> 
                  Reste: {{ (Number(order.total_sell_price) - Number(order.amount_paid)).toFixed(2) }}
                </span>
              </div>

              <!-- Buttons -->
              <div class="flex items-center gap-2">
                <button v-if="(Number(order.total_sell_price) - Number(order.amount_paid)) > 0.01" 
                  @click.stop="openOrderDetails(order)" 
                  class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:scale-105 active:scale-95 transition-all flex items-center">
                  PAYER
                </button>
                <button @click.stop="printInvoice(order)" class="w-10 h-10 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all" title="Imprimer">
                  <PrinterIcon class="w-5 h-5" />
                </button>
              </div>
            </div>

          </div>
        </TransitionGroup>

        <!-- Empty State -->
        <div v-if="filteredOrders.length === 0" class="py-32 flex flex-col items-center justify-center text-center">
          <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-6">
            <SearchIcon class="w-10 h-10 text-slate-300" />
          </div>
          <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-2">Aucune vente trouvée</h3>
          <p class="text-slate-500 font-bold max-w-sm">Les factures correspondant à votre recherche s'afficheront ici.</p>
        </div>
      </div>

    </div>

    <!-- Order Details Modal (Premium Redesign) -->
    <TransitionRoot appear :show="isModalOpen" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-50">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="duration-200 ease-in" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[2rem] bg-white text-left align-middle shadow-2xl transition-all w-full max-w-2xl mx-4 sm:mx-auto border border-slate-100">
                
                <!-- Modal Header -->
                <div class="px-8 pt-8 pb-6 bg-slate-50 border-b border-slate-100 flex justify-between items-start relative overflow-hidden">
                  <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl -mr-10 -mt-10"></div>
                  
                  <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase mb-3"
                      :class="(Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)) <= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                      {{ (Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)) <= 0 ? 'Facture Réglée' : 'Paiement en attente' }}
                    </span>
                    <DialogTitle as="h3" class="text-3xl font-black text-slate-900 tracking-tight">
                      Reçu {{ selectedOrder?.display_reference }}
                    </DialogTitle>
                    <p class="text-sm font-bold text-slate-500 mt-2 flex items-center">
                      <CalendarIcon class="w-4 h-4 mr-2" /> {{ selectedOrder ? formatDate(selectedOrder.created_at) : '' }}
                    </p>
                  </div>
                  
                  <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Client</p>
                    <p class="text-lg font-black text-indigo-900">{{ selectedOrder?.client?.name }}</p>
                  </div>
                </div>

                <div class="p-8">
                  <!-- Payment Banner (Glassmorphism) -->
                  <div v-if="(Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)) > 0.01" 
                    class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 rounded-3xl p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-inner">
                    <div>
                      <p class="text-emerald-900 font-black text-xl tracking-tight">Reste à payer</p>
                      <p class="text-emerald-600 font-bold text-sm mt-1">Saisissez le montant reçu ci-contre.</p>
                    </div>
                    <div class="flex items-center gap-3 w-full md:w-auto bg-white p-2 rounded-2xl shadow-sm border border-emerald-100/50">
                      <div class="relative flex-1 md:w-48">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-emerald-400 font-black text-lg">DH</span>
                        <input type="number" v-model="paymentAmount" 
                          class="w-full pl-12 pr-4 py-3 bg-slate-50 border-0 rounded-xl focus:ring-0 focus:bg-emerald-50 transition-colors font-black text-slate-800 text-lg" 
                          :placeholder="(Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)).toFixed(2)">
                      </div>
                      <button @click="addPayment" :disabled="!paymentAmount || paymentAmount <= 0" 
                        class="bg-emerald-500 text-white p-3 rounded-xl font-black hover:bg-emerald-600 disabled:opacity-50 shadow-md transition-all flex items-center justify-center">
                        <CheckCircleIcon class="w-6 h-6" />
                      </button>
                    </div>
                  </div>

                  <!-- Details Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    
                    <!-- Items -->
                    <div>
                      <h4 class="font-black text-slate-900 mb-4 flex items-center">
                        <span class="w-6 h-6 rounded-md bg-indigo-50 flex items-center justify-center mr-2"><ReceiptIcon class="w-3 h-3 text-indigo-600" /></span>
                        Articles ({{ selectedOrder?.lines?.length }})
                      </h4>
                      <ul class="space-y-3">
                        <li v-for="line in selectedOrder?.lines" :key="line.id" 
                          class="p-3 rounded-2xl border transition-all duration-300"
                          :class="line.quantity_returned >= line.quantity ? 'bg-rose-50 border-rose-100 opacity-80' : 'bg-slate-50 border-slate-100 hover:border-indigo-200'">
                          <div class="flex justify-between items-start mb-2">
                            <div class="flex-1 pr-4">
                              <span v-if="line.label" class="font-bold text-slate-700" :class="line.quantity_returned >= line.quantity ? 'line-through text-rose-900' : ''">{{ line.label }}</span>
                              <span v-else-if="line.item_type.includes('StockPanel')" class="font-bold text-slate-700" :class="line.quantity_returned >= line.quantity ? 'line-through text-rose-900' : ''">Px {{ line.item?.type }} {{ line.item?.size_x }}x{{ line.item?.size_y }}</span>
                              <span v-else-if="line.item_type.includes('StockCanto')" class="font-bold text-slate-700" :class="line.quantity_returned >= line.quantity ? 'line-through text-rose-900' : ''">Bandchant {{ line.item?.color_code }}</span>
                              <span v-else class="font-bold text-slate-700" :class="line.quantity_returned >= line.quantity ? 'line-through text-rose-900' : ''">Svc {{ line.item?.name }}</span>
                            </div>
                            <span class="font-black text-slate-900 px-2 py-1 bg-white rounded-lg shadow-sm text-xs">{{ Number(line.total_line_sell).toFixed(2) }} DH</span>
                          </div>
                          
                          <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                              <span class="text-[10px] font-black px-2 py-0.5 rounded-full" 
                                :class="line.quantity_returned >= line.quantity ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-600'">
                                Qté: {{ line.quantity }}
                              </span>
                              <span v-if="line.quantity_returned > 0" 
                                class="text-[10px] font-black px-2 py-0.5 rounded-full"
                                :class="line.quantity_returned >= line.quantity ? 'bg-rose-600 text-white animate-pulse' : 'bg-rose-100 text-rose-600'">
                                {{ line.quantity_returned >= line.quantity ? 'TOTALEMENT RETOURNÉ' : 'Retourné: ' + line.quantity_returned }}
                              </span>
                            </div>
                            <div v-if="line.quantity_returned > 0 && line.quantity_returned < line.quantity" class="text-[10px] font-black text-emerald-600 italic">
                              Reste: {{ line.quantity - line.quantity_returned }}
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>

                    <!-- Payments -->
                    <div>
                      <h4 class="font-black text-slate-900 mb-4 flex items-center">
                        <span class="w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mr-2"><CreditCardIcon class="w-3 h-3 text-emerald-600" /></span>
                        Historique Paiements
                      </h4>
                      <ul v-if="selectedOrder?.payments?.length" class="space-y-3">
                        <li v-for="pay in selectedOrder.payments" :key="pay.id" 
                          class="flex justify-between items-center text-sm border-l-2 pl-3 py-1 rounded-r-lg"
                          :class="pay.type === 'retour' ? 'border-rose-400 bg-rose-50/30' : 'border-emerald-400 bg-emerald-50/30'">
                          <div>
                            <span class="font-bold text-slate-700 block">{{ formatDate(pay.created_at) }}</span>
                            <span class="text-[10px] font-black uppercase tracking-widest" :class="pay.type === 'retour' ? 'text-rose-600' : 'text-emerald-600'">{{ pay.type }}</span>
                          </div>
                          <span class="font-black" :class="pay.type === 'retour' ? 'text-rose-600' : 'text-emerald-600'">
                            {{ pay.type === 'retour' ? '' : '+' }}{{ Number(pay.amount).toFixed(2) }}
                          </span>
                        </li>
                      </ul>
                      <div v-else class="h-20 flex items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50">
                        <p class="text-xs font-bold text-slate-400">Aucun paiement.</p>
                      </div>
                    </div>
                  </div>

                  <!-- Total Block (Dark Mode style) -->
                  <div class="bg-slate-900 text-white p-6 rounded-3xl shadow-xl relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10"></div>
                    
                    <div class="flex justify-between items-end relative z-10">
                      <div>
                        <p class="text-slate-400 font-bold text-sm mb-1">Montant Total</p>
                        <p class="text-4xl font-black tracking-tight">{{ Number(selectedOrder?.total_sell_price).toFixed(2) }} <span class="text-lg text-slate-500">DH</span></p>
                      </div>
                      
                      <div class="text-right">
                        <p v-if="selectedOrder?.total_refunded > 0" class="text-rose-400 font-bold text-xs mb-1 uppercase tracking-widest">Retourné: {{ Number(selectedOrder?.total_refunded).toFixed(2) }}</p>
                        <p class="text-emerald-400/80 font-bold text-xs mb-1 uppercase tracking-widest">Payé: {{ Number(selectedOrder?.amount_paid).toFixed(2) }}</p>
                        <p v-if="(Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)) > 0.01" class="text-amber-400 font-black text-lg">
                          Reste: {{ (Number(selectedOrder?.total_sell_price) - Number(selectedOrder?.amount_paid)).toFixed(2) }}
                        </p>
                      </div>
                    </div>
                  </div>

                </div>

                <!-- Footer -->
                <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-between items-center rounded-b-[2rem]">
                  <button type="button" 
                    class="px-5 py-2.5 text-sm font-black text-rose-600 bg-rose-50 border border-rose-100 rounded-xl hover:bg-rose-100 focus:outline-none transition-all flex items-center shadow-sm"
                    @click="openReturnModal">
                    <RotateCcwIcon class="w-4 h-4 mr-2" /> Retour / Avoir
                  </button>

                  <div class="flex gap-3">
                    <button type="button" class="px-6 py-2.5 text-sm font-black text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 focus:outline-none transition-colors" @click="closeModal">
                      Fermer
                    </button>
                    <button type="button" class="px-6 py-2.5 text-sm font-black text-white bg-indigo-600 rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 hover:shadow-indigo-600/50 focus:outline-none transition-all" @click="printInvoice(selectedOrder)">
                      Imprimer Facture
                    </button>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Return/Refund Modal -->
    <TransitionRoot appear :show="isReturnModalOpen" as="template">
      <Dialog as="div" @close="isReturnModalOpen = false" class="relative z-[60]" :initialFocus="returnFocusRef">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="duration-200 ease-in" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[2rem] bg-white text-left align-middle shadow-2xl transition-all w-full max-w-xl border border-rose-100">
                <div class="p-8">
                  <div class="flex items-center space-x-4 mb-6">
                    <div class="w-12 h-12 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600">
                      <RotateCcwIcon class="w-6 h-6" />
                    </div>
                    <div>
                      <DialogTitle as="h3" class="text-2xl font-black text-slate-900 tracking-tight">Gérer un Retour</DialogTitle>
                      <p class="text-sm font-bold text-slate-500">Sélectionnez les quantités à retourner.</p>
                    </div>
                  </div>

                  <div class="space-y-4 mb-8 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="line in returnForm.lines" :key="line.order_line_id" 
                      class="p-4 rounded-2xl border flex items-center justify-between gap-4 transition-all"
                      :class="line.max_quantity <= 0 ? 'bg-slate-100 border-slate-200 opacity-60 grayscale' : 'bg-slate-50 border-slate-100'">
                      <div class="flex-1">
                        <p class="font-bold text-slate-800 text-sm" :class="line.max_quantity <= 0 ? 'line-through' : ''">{{ line.label }}</p>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                          {{ line.max_quantity <= 0 ? 'Déjà totalement retourné' : 'Reste retournable: ' + line.max_quantity + ' | Prix: ' + line.unit_price + ' DH' }}
                        </p>
                      </div>
                      <div class="w-24">
                        <input type="number" v-model="line.quantity" 
                          :max="line.max_quantity" min="0" step="0.01"
                          :disabled="line.max_quantity <= 0"
                          class="w-full bg-white border-slate-200 rounded-xl px-3 py-2 text-center font-black text-rose-600 focus:ring-2 focus:ring-rose-500 transition-all disabled:opacity-30"
                          placeholder="0">
                      </div>
                    </div>
                  </div>

                  <div class="mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Motif du retour (Optionnel)</label>
                    <textarea v-model="returnForm.reason" rows="2" 
                      class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 transition-all"
                      placeholder="Ex: Erreur de commande, Article défectueux..."></textarea>
                  </div>

                  <div class="bg-rose-900 text-white p-6 rounded-3xl shadow-xl flex justify-between items-center">
                    <div>
                      <p class="text-rose-300 font-bold text-xs uppercase tracking-widest mb-1">Total à rembourser</p>
                      <p class="text-3xl font-black tracking-tight">{{ calculateTotalRefund().toFixed(2) }} <span class="text-lg text-rose-400">DH</span></p>
                    </div>
                    <button ref="returnFocusRef" @click.prevent="processReturn" :disabled="isSubmittingReturn || calculateTotalRefund() <= 0"
                      class="px-6 py-3 bg-white text-rose-900 rounded-2xl font-black hover:bg-rose-50 disabled:opacity-50 transition-all shadow-lg active:scale-95">
                      {{ isSubmittingReturn ? 'Traitement...' : 'Confirmer le Retour' }}
                    </button>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
const toast = useToast();
const page = usePage();
import { 
  FileTextIcon, FilterIcon, CheckCircleIcon, ClockIcon, 
  PrinterIcon, ReceiptIcon, SearchIcon, CreditCardIcon, CalendarIcon,
  RotateCwIcon, FileDownIcon, RotateCcwIcon
} from 'lucide-vue-next';
import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

const orders = ref([]);
const searchQuery = ref('');
const statusFilter = ref('all');
const isModalOpen = ref(false);
const selectedOrder = ref(null);
const paymentAmount = ref(null);
const isLoading = ref(true);
const isReturnModalOpen = ref(false);
const isSubmittingReturn = ref(false);
const returnFocusRef = ref(null);
const returnForm = ref({
  order_id: null,
  reason: '',
  lines: []
});

// Date Filter State
const activeDateFilter = ref('all'); // 'today', 'yesterday', 'week', 'month', 'custom', 'all'
const customStartDate = ref('');
const customEndDate = ref('');

// Stats Calculation
const stats = computed(() => {
  const totalSales = filteredOrders.value.reduce((sum, o) => sum + Number(o.total_sell_price), 0);
  const totalReturns = filteredOrders.value.reduce((sum, o) => sum + Number(o.total_refunded || 0), 0);
  return { totalSales, totalReturns };
});

const filteredOrders = computed(() => {
  return orders.value.filter(o => {
    // 1. Date Filter
    const date = new Date(o.created_at);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (activeDateFilter.value === 'today') {
      if (date < today) return false;
    } else if (activeDateFilter.value === 'yesterday') {
      if (date < yesterday || date >= today) return false;
    } else if (activeDateFilter.value === 'week') {
      const startOfWeek = new Date(today);
      const day = startOfWeek.getDay() || 7;
      if (day !== 1) startOfWeek.setHours(-24 * (day - 1));
      if (date < startOfWeek) return false;
    } else if (activeDateFilter.value === 'month') {
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
      if (date < startOfMonth) return false;
    } else if (activeDateFilter.value === 'custom') {
      // Robust Date Comparison using Local YYYY-MM-DD strings
      const orderDateLocal = new Date(o.created_at).toLocaleDateString('en-CA'); // "YYYY-MM-DD"
      
      if (customStartDate.value && orderDateLocal < customStartDate.value) return false;
      if (customEndDate.value && orderDateLocal > customEndDate.value) return false;
    }

    // 2. Status Filter
    const isPaid = Number(o.amount_paid) >= Number(o.total_sell_price);
    if (statusFilter.value === 'paid' && !isPaid) return false;
    if (statusFilter.value === 'unpaid' && isPaid) return false;

    // 3. Search Query (Reference, Client, Amount, Date)
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const refMatch = o.display_reference.toLowerCase().includes(q);
      const clientMatch = o.client?.name?.toLowerCase().includes(q);
      const amountMatch = o.total_sell_price.toString().includes(q) || (Number(o.total_sell_price) - Number(o.amount_paid)).toFixed(2).includes(q);
      const dateMatch = formatDate(o.created_at).toLowerCase().includes(q);
      
      if (!refMatch && !clientMatch && !amountMatch && !dateMatch) return false;
    }

    return true;
  });
});

const setDateFilter = (filter) => {
    activeDateFilter.value = filter;
    if(filter !== 'custom') {
        customStartDate.value = '';
        customEndDate.value = '';
    }
};

const openOrderDetails = (order) => {
  selectedOrder.value = order;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  setTimeout(() => { 
    selectedOrder.value = null; 
    paymentAmount.value = null;
  }, 300);
};

const addPayment = async () => {
  if(!paymentAmount.value || paymentAmount.value <= 0) return;
  
  const reste = Number(selectedOrder.value.total_sell_price) - Number(selectedOrder.value.amount_paid);
  if (paymentAmount.value > (reste + 0.01)) {
    toast.error('Le montant (' + paymentAmount.value + ' DH) est supérieur au reste à payer (' + reste.toFixed(2) + ' DH).');
    return;
  }

  try {
    const res = await axios.post(`/api/orders/${selectedOrder.value.id}/pay`, { 
      amount: paymentAmount.value,
      source: selectedOrder.value.source 
    });
    
    // Update local state to reflect new data
    const updatedOrder = res.data.order;
    const index = orders.value.findIndex(o => o.id === updatedOrder.id);
    if(index !== -1) {
      orders.value[index] = updatedOrder;
      selectedOrder.value = updatedOrder; // update modal instantly
    }
    paymentAmount.value = null;
    toast.success('Paiement ajouté avec succès!');
  } catch(e) {
    toast.error('Erreur lors de l\'ajout du paiement');
    console.error(e);
  }
};

const openReturnModal = () => {
  if (!selectedOrder.value) return;
  
  returnForm.value = {
    order_id: selectedOrder.value.id,
    reason: '',
    lines: selectedOrder.value.lines.map(line => ({
      order_line_id: line.id,
      label: line.label || (line.item_type.includes('StockPanel') ? `Px ${line.item?.type}` : line.item?.name),
      unit_price: Number(line.unit_sell_price),
      max_quantity: Number(line.quantity) - Number(line.quantity_returned || 0),
      quantity: 0
    }))
  };
  isReturnModalOpen.value = true;
};

const calculateTotalRefund = () => {
  return returnForm.value.lines.reduce((total, line) => {
    return total + (line.quantity * line.unit_price);
  }, 0);
};

const processReturn = async () => {
  const total = calculateTotalRefund();
  if (total <= 0) return;
  
  isSubmittingReturn.value = true;
  try {
    const payload = {
      reason: returnForm.value.reason,
      return_lines: returnForm.value.lines
        .filter(l => l.quantity > 0)
        .map(l => ({ order_line_id: l.order_line_id, quantity: l.quantity }))
    };

    const res = await axios.post(`/api/admin/orders/${returnForm.value.order_id}/return`, payload);
    toast.success(res.data.message);
    
    isReturnModalOpen.value = false;
    closeModal();
    loadOrders(); // Refresh everything
  } catch (e) {
    toast.error(e.response?.data?.error || 'Erreur lors du traitement du retour');
    console.error(e);
  } finally {
    isSubmittingReturn.value = false;
  }
};

const printInvoice = (order) => {
  if (!order) return;
  
  // Dispatch global print event
  window.dispatchEvent(new CustomEvent('global-print', {
    detail: {
      order: order,
      items: order.lines || [],
      total: Number(order.total_sell_price),
      amountPaid: Number(order.amount_paid) || 0,
      clientName: order.client?.name || 'Client'
    }
  }));
};

const loadOrders = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/orders');
    orders.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch(e) { 
    console.error(e); 
  } finally {
    isLoading.value = false;
  }
};

const formatDate = (dateStr) => {
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(dateStr));
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

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

onMounted(() => loadOrders());
</script>

<style scoped>
.shadow-soft {
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
}
</style>
