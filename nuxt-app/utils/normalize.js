// utils/normalize.ts
export const normalizeJa = (input = '') =>
    String(input)
    .normalize('NFKC')                 // 全半角など統一
    .toLowerCase()                     // 大小統一
    .replace(/\s+/g, '')               // 空白除去（任意）
    .replace(/[\u30a1-\u30f6]/g, ch => // カタカナ→ひらがな
            String.fromCharCode(ch.charCodeAt(0) - 0x60)
    );

export const normalizeFields = (...fields) =>
    normalizeJa(fields.filter(Boolean).join(' '));
