<template>
  <Teleport to="body">
    <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none" style="max-width: 420px;">
      <TransitionGroup name="toast">
        <div v-for="toast in toasts" :key="toast.id"
          class="pointer-events-auto rounded-2xl px-5 py-4 shadow-2xl border backdrop-blur-xl flex items-start gap-3 min-w-[300px]"
          :class="styles[toast.type]">
          
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5" :class="iconBg[toast.type]">
            <CheckCircleIcon v-if="toast.type === 'success'" class="w-4.5 h-4.5" />
            <XCircleIcon v-else-if="toast.type === 'error'" class="w-4.5 h-4.5" />
            <AlertTriangleIcon v-else-if="toast.type === 'warning'" class="w-4.5 h-4.5" />
            <InfoIcon v-else class="w-4.5 h-4.5" />
          </div>

          <p class="text-sm font-bold leading-relaxed flex-1 pt-1">{{ toast.message }}</p>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast';
import { CheckCircleIcon, XCircleIcon, AlertTriangleIcon, InfoIcon } from 'lucide-vue-next';

const { toasts } = useToast();

const styles = {
  success: 'bg-emerald-50/95 border-emerald-200/60 text-emerald-800',
  error: 'bg-rose-50/95 border-rose-200/60 text-rose-800',
  warning: 'bg-amber-50/95 border-amber-200/60 text-amber-800',
  info: 'bg-blue-50/95 border-blue-200/60 text-blue-800',
};

const iconBg = {
  success: 'bg-emerald-500/15 text-emerald-600',
  error: 'bg-rose-500/15 text-rose-600',
  warning: 'bg-amber-500/15 text-amber-600',
  info: 'bg-blue-500/15 text-blue-600',
};
</script>

<style scoped>
.toast-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.55, 0, 1, 0.45);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(80px) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(80px) scale(0.9);
}
</style>
