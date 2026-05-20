export function usePrint() {
  const printOrder = () => {
    // We rely on window.print() and CSS media queries (print:block)
    // to show only the template and hide everything else.
    // Tailwind's print: modifier is perfect for this.
    window.print();
  };

  return {
    printOrder
  };
}
