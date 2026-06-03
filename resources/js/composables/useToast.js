import { ref } from 'vue';

const toasts = ref([]);
let idCounter = 0;

const remove = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id);
};

const addToast = (message, type = 'success', duration = 3500) => {
  const id = ++idCounter;
  toasts.value.push({ id, message, type, duration });
  if (duration !== Infinity) {
    setTimeout(() => remove(id), duration);
  }
};

export function useToast() {
  return {
    toasts,
    remove,
    success: (msg) => addToast(msg, 'success'),
    error: (msg) => addToast(msg, 'error', 5000),
    warning: (msg) => addToast(msg, 'warning', 4000),
    info: (msg) => addToast(msg, 'info'),
  };
}
