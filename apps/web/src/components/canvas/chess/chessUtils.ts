import { PieceData, PieceType, PieceColor } from './ChessPiece';

export type BoardState = (PieceData | null)[][];

export const INITIAL_BOARD: BoardState = [
  [
    { type: 'r', color: 'b' },
    { type: 'n', color: 'b' },
    { type: 'b', color: 'b' },
    { type: 'q', color: 'b' },
    { type: 'k', color: 'b' },
    { type: 'b', color: 'b' },
    { type: 'n', color: 'b' },
    { type: 'r', color: 'b' }
  ],
  Array(8).fill(null).map(() => ({ type: 'p', color: 'b' })),
  Array(8).fill(null),
  Array(8).fill(null),
  Array(8).fill(null),
  Array(8).fill(null),
  Array(8).fill(null).map(() => ({ type: 'p', color: 'w' })),
  [
    { type: 'r', color: 'w' },
    { type: 'n', color: 'w' },
    { type: 'b', color: 'w' },
    { type: 'q', color: 'w' },
    { type: 'k', color: 'w' },
    { type: 'b', color: 'w' },
    { type: 'n', color: 'w' },
    { type: 'r', color: 'w' }
  ]
];

// Mikhail Tal Tactical Sacrifice Setup
export const TAL_SACRIFICE_BOARD: BoardState = [
  [
    { type: 'r', color: 'b' },
    null,
    { type: 'b', color: 'b' },
    { type: 'q', color: 'b' },
    null,
    { type: 'r', color: 'b' },
    { type: 'k', color: 'b' },
    null
  ],
  [
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    null,
    null,
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' }
  ],
  [
    null,
    null,
    { type: 'n', color: 'b' },
    { type: 'p', color: 'b' },
    null,
    null,
    null,
    null
  ],
  Array(8).fill(null),
  [
    null,
    null,
    { type: 'b', color: 'w' },
    null,
    { type: 'p', color: 'w' },
    null,
    null,
    null
  ],
  [
    null,
    null,
    { type: 'n', color: 'w' },
    null,
    null,
    { type: 'n', color: 'w' },
    null,
    null
  ],
  [
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    null,
    null,
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' }
  ],
  [
    { type: 'r', color: 'w' },
    null,
    { type: 'b', color: 'w' },
    { type: 'q', color: 'w' },
    null,
    { type: 'r', color: 'w' },
    { type: 'k', color: 'w' },
    null
  ]
];

// Lucena Endgame Setup
export const LUCENA_BOARD: BoardState = [
  [null, null, null, null, null, null, null, { type: 'k', color: 'b' }],
  [null, null, null, null, { type: 'k', color: 'w' }, null, null, null],
  [null, null, null, null, { type: 'p', color: 'w' }, null, null, null],
  Array(8).fill(null),
  [null, { type: 'r', color: 'w' }, null, null, null, null, null, null],
  Array(8).fill(null),
  Array(8).fill(null),
  [null, null, null, null, { type: 'r', color: 'b' }, null, null, null]
];

// Smothered Mate Setup
export const SMOTHERED_BOARD: BoardState = [
  [
    { type: 'r', color: 'b' },
    null,
    null,
    null,
    null,
    { type: 'r', color: 'b' },
    { type: 'k', color: 'b' },
    null
  ],
  [
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    null,
    null,
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' }
  ],
  Array(8).fill(null),
  Array(8).fill(null),
  Array(8).fill(null),
  [
    null,
    null,
    null,
    null,
    null,
    { type: 'n', color: 'w' },
    null,
    null
  ],
  [
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    null,
    null,
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' }
  ],
  [
    null,
    null,
    null,
    { type: 'q', color: 'w' },
    null,
    null,
    { type: 'k', color: 'w' },
    null
  ]
];

// Greek Gift Sacrifice Setup (Classical Bxh7+)
export const GREEK_GIFT_BOARD: BoardState = [
  [
    { type: 'r', color: 'b' },
    null,
    { type: 'b', color: 'b' },
    { type: 'q', color: 'b' },
    null,
    { type: 'r', color: 'b' },
    { type: 'k', color: 'b' },
    null
  ],
  [
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    null,
    { type: 'b', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' },
    { type: 'p', color: 'b' }
  ],
  [
    null,
    null,
    { type: 'n', color: 'b' },
    { type: 'p', color: 'b' },
    null,
    { type: 'n', color: 'b' },
    null,
    null
  ],
  Array(8).fill(null),
  [
    null,
    null,
    { type: 'b', color: 'w' },
    null,
    { type: 'p', color: 'w' },
    null,
    null,
    null
  ],
  [
    null,
    null,
    { type: 'n', color: 'w' },
    null,
    null,
    { type: 'n', color: 'w' },
    null,
    null
  ],
  [
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    null,
    null,
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' },
    { type: 'p', color: 'w' }
  ],
  [
    { type: 'r', color: 'w' },
    null,
    { type: 'b', color: 'w' },
    { type: 'q', color: 'w' },
    null,
    { type: 'r', color: 'w' },
    { type: 'k', color: 'w' },
    null
  ]
];

// Piece Values for Material Balance
export const PIECE_VALUES: Record<PieceType, number> = {
  p: 1,
  n: 3,
  b: 3,
  r: 5,
  q: 9,
  k: 0
};

// Calculate Material Balance
export function calculateMaterial(board: BoardState) {
  let whiteMaterial = 0;
  let blackMaterial = 0;
  const whitePieces: PieceType[] = [];
  const blackPieces: PieceType[] = [];

  for (let r = 0; r < 8; r++) {
    for (let c = 0; c < 8; c++) {
      const piece = board[r][c];
      if (piece) {
        if (piece.color === 'w') {
          whiteMaterial += PIECE_VALUES[piece.type];
          whitePieces.push(piece.type);
        } else {
          blackMaterial += PIECE_VALUES[piece.type];
          blackPieces.push(piece.type);
        }
      }
    }
  }

  // Standard starting pieces count
  const standardPieces: PieceType[] = [
    'p', 'p', 'p', 'p', 'p', 'p', 'p', 'p',
    'r', 'r', 'n', 'n', 'b', 'b', 'q'
  ];

  // Captured pieces: what standard pieces are missing from current board
  const capturedWhitePieces: PieceType[] = [];
  const capturedBlackPieces: PieceType[] = [];

  const tempWhite = [...whitePieces];
  const tempBlack = [...blackPieces];

  for (const st of standardPieces) {
    const idxW = tempWhite.indexOf(st);
    if (idxW !== -1) {
      tempWhite.splice(idxW, 1);
    } else {
      capturedWhitePieces.push(st);
    }

    const idxB = tempBlack.indexOf(st);
    if (idxB !== -1) {
      tempBlack.splice(idxB, 1);
    } else {
      capturedBlackPieces.push(st);
    }
  }

  return {
    whiteMaterial,
    blackMaterial,
    diff: whiteMaterial - blackMaterial,
    capturedWhitePieces,
    capturedBlackPieces
  };
}

// Generate Legal Moves for a selected piece
export function getLegalMoves(r: number, c: number, board: BoardState): [number, number][] {
  const piece = board[r][c];
  if (!piece) return [];

  const moves: [number, number][] = [];
  const color = piece.color;
  const oppColor = color === 'w' ? 'b' : 'w';

  const inBounds = (row: number, col: number) => row >= 0 && row < 8 && col >= 0 && col < 8;

  switch (piece.type) {
    case 'p': {
      const dir = color === 'w' ? -1 : 1;
      const startRow = color === 'w' ? 6 : 1;

      // 1 square forward
      if (inBounds(r + dir, c) && !board[r + dir][c]) {
        moves.push([r + dir, c]);

        // 2 squares forward from starting rank
        if (r === startRow && inBounds(r + 2 * dir, c) && !board[r + 2 * dir][c]) {
          moves.push([r + 2 * dir, c]);
        }
      }

      // Diagonal captures
      for (const dc of [-1, 1]) {
        if (inBounds(r + dir, c + dc)) {
          const target = board[r + dir][c + dc];
          if (target && target.color === oppColor) {
            moves.push([r + dir, c + dc]);
          }
        }
      }
      break;
    }

    case 'n': {
      const offsets = [
        [-2, -1], [-2, 1], [-1, -2], [-1, 2],
        [1, -2], [1, 2], [2, -1], [2, 1]
      ];
      for (const [dr, dc] of offsets) {
        const nr = r + dr;
        const nc = c + dc;
        if (inBounds(nr, nc)) {
          const target = board[nr][nc];
          if (!target || target.color === oppColor) {
            moves.push([nr, nc]);
          }
        }
      }
      break;
    }

    case 'b': {
      const dirs = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
      for (const [dr, dc] of dirs) {
        let nr = r + dr;
        let nc = c + dc;
        while (inBounds(nr, nc)) {
          const target = board[nr][nc];
          if (!target) {
            moves.push([nr, nc]);
          } else {
            if (target.color === oppColor) moves.push([nr, nc]);
            break;
          }
          nr += dr;
          nc += dc;
        }
      }
      break;
    }

    case 'r': {
      const dirs = [[-1, 0], [1, 0], [0, -1], [0, 1]];
      for (const [dr, dc] of dirs) {
        let nr = r + dr;
        let nc = c + dc;
        while (inBounds(nr, nc)) {
          const target = board[nr][nc];
          if (!target) {
            moves.push([nr, nc]);
          } else {
            if (target.color === oppColor) moves.push([nr, nc]);
            break;
          }
          nr += dr;
          nc += dc;
        }
      }
      break;
    }

    case 'q': {
      const dirs = [
        [-1, -1], [-1, 1], [1, -1], [1, 1],
        [-1, 0], [1, 0], [0, -1], [0, 1]
      ];
      for (const [dr, dc] of dirs) {
        let nr = r + dr;
        let nc = c + dc;
        while (inBounds(nr, nc)) {
          const target = board[nr][nc];
          if (!target) {
            moves.push([nr, nc]);
          } else {
            if (target.color === oppColor) moves.push([nr, nc]);
            break;
          }
          nr += dr;
          nc += dc;
        }
      }
      break;
    }

    case 'k': {
      const dirs = [
        [-1, -1], [-1, 0], [-1, 1],
        [0, -1],           [0, 1],
        [1, -1],  [1, 0],  [1, 1]
      ];
      for (const [dr, dc] of dirs) {
        const nr = r + dr;
        const nc = c + dc;
        if (inBounds(nr, nc)) {
          const target = board[nr][nc];
          if (!target || target.color === oppColor) {
            moves.push([nr, nc]);
          }
        }
      }
      break;
    }
  }

  return moves;
}

// Convert board coordinates to standard algebraic chess notation (e.g. "e4", "Nf3")
export function toAlgebraic(
  piece: PieceData,
  fromR: number,
  fromC: number,
  toR: number,
  toC: number,
  isCapture: boolean
): string {
  const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
  const rank = 8 - toR;
  const file = files[toC];

  if (piece.type === 'p') {
    if (isCapture) {
      return `${files[fromC]}x${file}${rank}`;
    }
    return `${file}${rank}`;
  }

  const pieceLetter = piece.type.toUpperCase();
  const captureSign = isCapture ? 'x' : '';
  return `${pieceLetter}${captureSign}${file}${rank}`;
}

// Synthesize a satisfying wooden piece placement sound with Web Audio
export function playMoveSound(isCapture = false) {
  try {
    const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = isCapture ? 'triangle' : 'sine';
    const baseFreq = isCapture ? 260 : 180;
    osc.frequency.setValueAtTime(baseFreq, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(80, ctx.currentTime + 0.08);

    gain.gain.setValueAtTime(0.2, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + 0.09);
  } catch (_) {}
}
