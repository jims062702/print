export const PRICES = {
  textOnly: {
    bw: { A4: 2, Short: 2, Long: 3 },
    partial: { A4: 3, Short: 3, Long: 4 },
    full: { A4: 4, Short: 4, Long: 5 }
  },
  withImage: {
    bw: { A4: 4, Short: 4, Long: 5 },
    partial: { A4: 5, Short: 5, Long: 8 },
    full: { A4: 8, Short: 8, Long: 10 }
  },
  imageOnly: {
    bw: { A4: 5, Short: 5, Long: 8 },
    half: { A4: 6, Short: 6, Long: 8 },
    full: { A4: 8, Short: 8, Long: 10 }
  },
  photocopy: {
    bw: { A4: 2, Short: 2, Long: 3 },
    partial: { A4: 5, Short: 5, Long: 6 },
    full: { A4: 7, Short: 7, Long: 8 }
  },
  backToBackExtra: { bw: 1, partial: 2, full: 2 },
  rushId: {
    pkg1: 35,
    pkg2: 40,
    pkg3: 50,
    pkg4: 50
  }
};

export function computePrice(item) {
  // item: { category, colorMode, paperSize, isBackToBack, pages, quantity }
  const { category, colorMode, paperSize, isBackToBack, pages = 1, quantity = 1 } = item;
  let base = 0;

  if (category === 'textOnly') {
    base = PRICES.textOnly[colorMode][paperSize];
  } else if (category === 'withImage') {
    base = PRICES.withImage[colorMode][paperSize];
  } else if (category === 'imageOnly') {
    base = PRICES.imageOnly[colorMode][paperSize];
  } else if (category === 'photocopy') {
    base = PRICES.photocopy[colorMode][paperSize];
  } else if (category.startsWith('rushId:')) {
    const key = category.split(':')[1];
    return PRICES.rushId[key] * quantity;
  }

  let total = base * pages * quantity;
  if (isBackToBack) {
    const extraMap = PRICES.backToBackExtra;
    const extraKey = colorMode === 'bw' ? 'bw' : (colorMode === 'partial' ? 'partial' : 'full');
    total += extraMap[extraKey] * quantity;
  }
  return total;
}

