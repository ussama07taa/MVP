<template>
  <div class="min-h-screen bg-slate-900 pb-24 font-sans select-none selection:bg-brand-500/30 overflow-x-hidden">
    
    <!-- Ultra-Refined Sticky Header -->
    <header class="sticky top-0 z-[60] bg-slate-900/90 backdrop-blur-2xl px-4 sm:px-6 py-4 border-b border-white/[0.05] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-500/20">
          <HammerIcon class="w-5 h-5 text-white" />
        </div>
        <div class="flex flex-col">
          <h1 class="text-xl font-black text-white tracking-[0.1em] uppercase leading-none">Khedma</h1>
          <span class="text-[8px] font-bold text-slate-500 uppercase tracking-[0.2em] mt-1">Atelier Pro</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <!-- Compact Control Buttons -->
        <button @click="toggleMute" class="w-11 h-11 bg-white/[0.03] border border-white/10 rounded-xl flex items-center justify-center active:scale-90 transition-all text-xs" :class="isMuted ? 'text-rose-400' : 'text-emerald-400'">
          <component :is="isMuted ? VolumeXIcon : Volume2Icon" class="w-5 h-5" />
        </button>
        <button @click="fetchQueue" class="w-11 h-11 bg-white/[0.03] border border-white/10 rounded-xl text-white flex items-center justify-center active:scale-90 transition-all">
          <RefreshCwIcon :class="{'animate-spin': isLoading}" class="w-5 h-5" />
        </button>
      </div>
    </header>

    <main class="p-3 sm:p-5 space-y-6 max-w-2xl mx-auto">
      
      <!-- High-Impact Mobile Progress -->
      <div v-if="queue.length > 0" class="px-1 group">
         <div class="flex justify-between items-center mb-2 px-1">
            <div class="flex items-center gap-2">
              <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></div>
              <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em]">Avancement</span>
            </div>
            <span class="text-2xl font-black italic bg-clip-text text-transparent bg-gradient-to-r from-brand-400 to-brand-100">{{ totalDonePercent }}%</span>
         </div>
         <div class="w-full h-2 bg-white/5 rounded-full border border-white/[0.05] overflow-hidden">
            <div class="h-full bg-gradient-to-r from-brand-600 to-brand-400 rounded-full transition-all duration-1000 relative shadow-[0_0_15px_rgba(99,102,241,0.4)]" :style="{ width: totalDonePercent + '%' }">
               <div class="absolute inset-0 bg-[linear-gradient(90deg,transparent_0%,rgba(255,255,255,0.3)_50%,transparent_100%)] animate-shimmer-fast opacity-30"></div>
            </div>
         </div>
      </div>

      <!-- Mobile-Optimized Search & Filters -->
      <div v-if="queue.length > 0" class="px-1 space-y-4">
        <div class="relative group">
          <SearchIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-brand-500 transition-colors" />
          <input v-model="searchQuery" type="text" placeholder="Chercher un ticket..." class="w-full pl-12 pr-4 py-4 bg-white/[0.03] border border-white/10 rounded-2xl focus:outline-none focus:border-brand-500/50 text-white font-bold text-sm transition-all placeholder:text-slate-600 backdrop-blur-md" />
        </div>
        
        <div v-if="uniqueMaterials.length > 0" class="flex gap-2 overflow-x-auto pb-1 scrollbar-none mask-fade-right">
          <button @click="selectedMaterial = 'all'" 
            class="px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest border shrink-0 transition-all active:scale-95"
            :class="selectedMaterial === 'all' ? 'bg-brand-500 border-brand-400 text-white shadow-lg shadow-brand-500/20' : 'bg-white/[0.03] border-white/10 text-slate-500'">
            Tous
          </button>
          <button v-for="mat in uniqueMaterials" :key="mat" @click="selectedMaterial = mat" 
            class="px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest border shrink-0 transition-all active:scale-95"
            :class="selectedMaterial === mat ? 'bg-brand-500 border-brand-400 text-white shadow-lg shadow-brand-500/20' : 'bg-white/[0.03] border-white/10 text-slate-500'">
            {{ mat }}
          </button>
        </div>
      </div>

      <!-- Dynamic Job Feed -->
      <div class="space-y-6 pb-6">
        <div v-for="job in filteredQueue" :key="job.id" 
          class="relative bg-white/[0.02] border border-white/[0.08] rounded-[2.5rem] overflow-hidden transition-all duration-500"
          :class="{'ring-2 ring-brand-500/40 bg-slate-800/30 scale-[1.01] shadow-2xl shadow-brand-500/10': job.status === 'in_progress'}">
          
          <!-- Job Header (Thumb Optimized) -->
          <div class="p-5 sm:p-6 border-b border-white/[0.04] flex items-center justify-between bg-gradient-to-b from-white/[0.01] to-transparent">
             <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white text-slate-950 rounded-2xl flex items-center justify-center shadow-lg shrink-0 transition-transform group-hover:rotate-2">
                  <span class="text-xl font-black">{{ job.queue_number.replace('Q-', '') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-1">
                    <h3 class="text-white font-black text-xl tracking-tight truncate">{{ job.client_name }}</h3>
                    <div class="flex items-center gap-2">
                      <a v-if="job.tefsil_url" :href="job.tefsil_url" target="_blank"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-slate-950 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-lg shadow-amber-500/20 active:scale-95 transition-all">
                        <FileTextIcon class="w-3 h-3" />
                        Plan
                      </a>
                      <div v-if="job.is_priority" class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-rose-500 text-white rounded-xl text-[9px] font-black uppercase tracking-wider shadow-lg shadow-rose-500/30 animate-pulse transition-all">
                        <ZapIcon class="w-3 h-3 fill-current" />
                        Urgent
                      </div>
                      <span class="text-slate-400 font-black text-lg tracking-tighter">{{ job.queue_number }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="job.status === 'in_progress' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-700'"></div>
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                      {{ job.status === 'in_progress' ? 'En cours' : 'Attente' }}
                    </span>
                  </div>
                </div>
             </div>
             <div class="text-right shrink-0">
                <div class="text-lg font-black text-white tabular-nums">{{ job.waiting_since }}'</div>
                <div class="text-[8px] font-bold text-slate-600 uppercase tracking-tighter">Depuis</div>
             </div>
          </div>

          <!-- Task Execution Area -->
          <div class="p-4 sm:p-6 grid grid-cols-1 gap-3">
            <button v-for="service in job.services" :key="service.id" 
              @click="toggleService(service)"
              class="relative w-full p-4 sm:p-5 rounded-[1.8rem] border-2 transition-all duration-300 flex items-center justify-between active:scale-[0.97] overflow-hidden group/item"
              :class="service.is_done ? 'bg-emerald-600 border-emerald-400 shadow-md shadow-emerald-900/20' : 'bg-slate-900/40 border-white/5'">
              
                <div class="flex items-center gap-4 sm:gap-5 relative z-10 min-w-0 mr-2">
                  <!-- Smart Quantity Badge -->
                  <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex flex-col items-center justify-center shrink-0 transition-transform group-item-hover:scale-105"
                    :class="service.is_done ? 'bg-white/20 text-white' : 'bg-brand-500 text-white shadow-lg shadow-brand-500/20'">
                    <span class="text-[7px] font-bold uppercase opacity-60 leading-none mb-0.5">Qty</span>
                    <span class="text-lg font-black leading-none">{{ service.quantity }}</span>
                  </div>
                  
                  <div class="text-left min-w-0">
                     <span class="block font-bold text-sm sm:text-base leading-[1.2] tracking-tight transition-colors line-clamp-2" 
                      :class="service.is_done ? 'text-white' : 'text-slate-100'">
                      {{ service.label }}
                     </span>
                     
                     <div class="flex flex-wrap gap-2 mt-2">
                        <div v-if="service.material_color || service.material_type" class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider border transition-colors"
                          :class="service.is_done ? 'bg-white/10 border-white/20 text-white' : 'bg-slate-800 border-white/5 text-slate-500 group-item-hover:text-slate-400'">
                          {{ service.material_color || service.material_type }}
                        </div>
                     </div>
                  </div>
                </div>

              <!-- Compact Status -->
              <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center border-[3px] transition-all duration-500"
                :class="service.is_done ? 'bg-white border-white text-emerald-600 rotate-0 scale-100' : 'bg-transparent border-white/10 text-transparent rotate-90 scale-75 group-item-hover:border-brand-500/30'">
                <CheckIcon class="w-6 h-6 stroke-[4px]" />
              </div>
            </button>
          </div>

          <!-- Master Finish Action -->
          <Transition name="finish-btn">
            <div v-if="job.all_done" class="px-5 pb-6">
               <button @click="hideJob(job)" class="w-full py-6 bg-emerald-500 text-white rounded-[1.8rem] font-black text-xl uppercase tracking-[0.2em] shadow-xl shadow-emerald-500/30 active:scale-95 transition-all flex items-center justify-center gap-4 relative overflow-hidden group/finish">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-finish-hover:translate-x-full transition-transform duration-1000"></div>
                  <CheckCircle2Icon class="w-7 h-7" /> SALIT !
               </button>
            </div>
          </Transition>

          <!-- Notes Insight (Compact) -->
          <div v-if="job.notes" class="px-6 py-4 bg-amber-500/[0.04] border-t border-white/[0.03] flex items-start gap-3">
             <InfoIcon class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
             <p class="text-[11px] font-medium text-amber-200/50 leading-tight italic line-clamp-2">"{{ job.notes }}"</p>
          </div>
        </div>
      </div>
    </main>

    <!-- Thumb-Reach Status Bar -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-slate-900/95 backdrop-blur-xl border-t border-white/10 z-[70]">
       <div class="flex items-center justify-center gap-3 py-3 max-w-sm mx-auto bg-white/[0.03] rounded-2xl border border-white/[0.05] shadow-inner">
          <div class="relative w-2.5 h-2.5">
            <div class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-30"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 relative z-10 shadow-[0_0_10px_rgba(16,185,129,0.7)]"></div>
          </div>
          <span class="text-[9px] font-black text-white/70 uppercase tracking-[0.25em]">Live Connection</span>
       </div>
    </div>

    <!-- Success Feedback Overlay -->
    <Transition name="toast">
      <div v-if="toast" class="fixed inset-0 z-[200] flex items-center justify-center p-8 pointer-events-none">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white px-8 py-7 rounded-[3rem] shadow-2xl flex flex-col items-center gap-4 border-2 border-white/20 animate-in zoom-in slide-in-from-bottom-20 pointer-events-auto">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center animate-bounce">
            <CheckCircle2Icon class="w-10 h-10" />
          </div>
          <span class="text-xl font-black uppercase tracking-widest text-center">{{ toast }}</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
  RefreshCwIcon, CheckIcon, HammerIcon, 
  InfoIcon, CheckCircle2Icon, ClockIcon, ScissorsIcon, 
  LayoutIcon, DrillIcon, WrenchIcon, LayersIcon,
  Volume2Icon, VolumeXIcon, SearchIcon, FileTextIcon, ZapIcon
} from 'lucide-vue-next';

const queue = ref([]);
const isLoading = ref(false);
const toast = ref(null);
let pollInterval = null;

// Search & Filtering State
const searchQuery = ref('');
const selectedMaterial = ref('all');

const uniqueMaterials = computed(() => {
  const mats = new Set();
  queue.value.forEach(job => {
    if (job.services) {
      job.services.forEach(s => {
        if (s.material_color) mats.add(s.material_color);
        else if (s.material_type) mats.add(s.material_type);
      });
    }
  });
  return Array.from(mats);
});

const filteredQueue = computed(() => {
  let res = [...queue.value];
  
  // Filter by search query (Client name or Queue number)
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    res = res.filter(job => 
      job.client_name.toLowerCase().includes(q) || 
      job.queue_number.toLowerCase().includes(q)
    );
  }
  
  // Filter by selected material (raw panel type/color)
  if (selectedMaterial.value !== 'all') {
    res = res.filter(job => 
      job.services && job.services.some(s => s.material_color === selectedMaterial.value || s.material_type === selectedMaterial.value)
    );
  }

  // Master Sort: Priority first, then FIFO (Oldest first)
  return res.sort((a, b) => {
    if (a.is_priority && !b.is_priority) return -1;
    if (!a.is_priority && b.is_priority) return 1;
    return a.id - b.id;
  });
});

// Premium Audio Engine (Uses Browser Web Audio API Synthesizer)
const isMuted = ref(localStorage.getItem('workshop_muted') === 'true');

const playSynthesizedSound = (freqs, type = 'sine', duration = 0.1) => {
  if (isMuted.value) return;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    let time = ctx.currentTime;
    freqs.forEach(([freq, delay, vol]) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = type;
      osc.frequency.setValueAtTime(freq, time + delay);
      gain.gain.setValueAtTime(vol || 0.1, time + delay);
      gain.gain.exponentialRampToValueAtTime(0.0001, time + delay + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time + delay);
      osc.stop(time + delay + duration);
    });
  } catch (e) {
    console.error('AudioContext synthesis failed', e);
  }
};

// Ascent smart chime for brand-new jobs sent from the cashier
const playNewJobChime = () => {
  playSynthesizedSound([
    [523.25, 0, 0.15],      // C5
    [659.25, 0.08, 0.15],   // E5
    [783.99, 0.16, 0.15],   // G5
    [1046.50, 0.24, 0.2]    // C6
  ], 'triangle', 0.45);
};

// High-pitch satisfying ding for service checkoff
const playCheckoffDing = () => {
  playSynthesizedSound([
    [880, 0, 0.1],          // A5
    [1760, 0.04, 0.15]      // A6
  ], 'sine', 0.25);
};

// Celebration major chord for completing a full client ticket (SALIT!)
const playTriumphFanfare = () => {
  playSynthesizedSound([
    [523.25, 0, 0.15],      // C5
    [659.25, 0.04, 0.15],   // E5
    [783.99, 0.08, 0.15],   // G5
    [1318.51, 0.12, 0.2]    // E6
  ], 'sine', 0.55);
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  localStorage.setItem('workshop_muted', isMuted.value ? 'true' : 'false');
  if (!isMuted.value) {
    playCheckoffDing(); // Play test ding to verify speaker output
  }
};



const totalDonePercent = computed(() => {
  if (queue.value.length === 0) return 0;
  const total = queue.value.reduce((acc, job) => acc + job.services_total, 0);
  const done = queue.value.reduce((acc, job) => acc + job.services_done, 0);
  return total > 0 ? Math.round((done / total) * 100) : 0;
});

const getServiceIcon = (label) => {
  const l = label.toLowerCase();
  if (l.includes('decoupe') || l.includes('cut')) return ScissorsIcon;
  if (l.includes('canto') || l.includes('chant')) return LayersIcon;
  if (l.includes('perçage') || l.includes('drill')) return LayoutIcon;
  if (l.includes('assemblage') || l.includes('mount')) return WrenchIcon;
  return HammerIcon;
};

const toggleService = async (service) => {
  try {
    const originalValue = service.is_done;
    service.is_done = !service.is_done;
    
    if (window.navigator.vibrate) window.navigator.vibrate([30, 50, 30]);
    
    if (!originalValue) {
      playCheckoffDing();
      showToast('NADI !');
    }
    
    await axios.post(`/api/workshop/services/${service.id}/toggle`);
    await fetchQueue();
  } catch (error) {
    console.error('Toggle error', error);
    fetchQueue();
  }
};

const hideJob = async (job) => {
  try {
    if (window.navigator.vibrate) window.navigator.vibrate([100, 50, 100]);
    playTriumphFanfare();
    await axios.post(`/api/workshop/queue/${job.id}/hide`);
    showToast('mchaat !');
    await fetchQueue();
  } catch (error) {
    console.error('Hide error', error);
  }
};

const showToast = (msg) => {
  toast.value = msg;
  setTimeout(() => { toast.value = null; }, 2000);
};

const fetchQueue = async (force = false) => {
  if (isLoading.value && !force) return;
  isLoading.value = true;
  try {
    const response = await axios.get('/api/workshop/queue');
    
    // Compare job IDs to trigger brand new job chimes
    const oldIds = queue.value.map(j => j.id);
    const newIds = response.data.map(j => j.id);
    const hasNewJob = newIds.some(id => !oldIds.includes(id));
    if (queue.value.length > 0 && hasNewJob) {
      playNewJobChime();
    }
    
    queue.value = response.data;
  } catch (error) {
    console.error('Fetch error', error);
  } finally {
    isLoading.value = false;
  }
};

const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    fetchQueue(true);
    startPolling();
  } else {
    stopPolling();
  }
};

const startPolling = () => {
  stopPolling();
  pollInterval = setInterval(() => fetchQueue(), 15000); // Snappy 15 seconds polling
};

const stopPolling = () => {
  if (pollInterval) clearInterval(pollInterval);
};

onMounted(() => {
  fetchQueue();
  startPolling();
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
  stopPolling();
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<style scoped>
.mask-fade-right {
  mask-image: linear-gradient(to right, black 85%, transparent 100%);
}

.animate-shimmer-fast {
  animation: shimmer-fast 2s infinite linear;
}

@keyframes shimmer-fast {
  from { transform: translateX(-100%); }
  to { transform: translateX(100%); }
}

.animate-shimmer-once {
  animation: shimmer-fast 1s ease-out forwards;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(100px) scale(0.8);
}

/* Toast Transitions */
.toast-enter-active {
  animation: toast-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
  animation: toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) reverse;
}

@keyframes toast-in {
  0% { opacity: 0; transform: translateY(100px) scale(0.5) rotate(10deg); }
  100% { opacity: 1; transform: translateY(0) scale(1) rotate(0); }
}

/* Finish Button Transition */
.finish-btn-enter-active {
  animation: finish-in 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes finish-in {
  0% { opacity: 0; transform: translateY(50px) scale(0.9); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
