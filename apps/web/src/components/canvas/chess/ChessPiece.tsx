import React from 'react';

export type PieceColor = 'w' | 'b';
export type PieceType = 'p' | 'n' | 'b' | 'r' | 'q' | 'k';

export interface PieceData {
  type: PieceType;
  color: PieceColor;
}

interface ChessPieceProps {
  type: PieceType;
  color: PieceColor;
  size?: number;
  isDragging?: boolean;
  isSelected?: boolean;
}

export const ChessPiece: React.FC<ChessPieceProps> = ({
  type,
  color,
  size = 46,
  isSelected = false
}) => {
  const isWhite = color === 'w';

  // SVG Paths for Staunton chess pieces
  const renderPath = () => {
    switch (type) {
      case 'p': // Pawn
        return (
          <g>
            <path
              d="M22 9c-2.2 0-4 1.8-4 4 0 1.2.5 2.3 1.4 3-1.6 1.1-2.9 2.8-3.4 5h12c-.5-2.2-1.8-3.9-3.4-5 .9-.7 1.4-1.8 1.4-3 0-2.2-1.8-4-4-4z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M15 21h14v7H15z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M12 28h20v6c0 1.1-.9 2-2 2H14c-1.1 0-2-.9-2-2v-6z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
          </g>
        );

      case 'n': // Knight
        return (
          <g>
            <path
              d="M22 10c-3 0-5.5 2.5-5.5 5.5 0 .8.2 1.6.5 2.3-2.5 1.5-4 4.2-4 7.2v3h6v4h12v-4c0-3.3-2.7-6-6-6h-1c-.5-1.5-.2-3.2.8-4.5.8-1 2.2-1.5 3.5-1.5h1.2V10H22z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <circle cx="26" cy="14" r="1.5" fill={isWhite ? '#1E293B' : '#38BDF8'} />
            <path
              d="M11 32h22v4c0 1.1-.9 2-2 2H13c-1.1 0-2-.9-2-2v-4z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
          </g>
        );

      case 'b': // Bishop
        return (
          <g>
            <circle cx="22" cy="8" r="2" fill={isWhite ? '#FDE047' : '#94A3B8'} />
            <path
              d="M17 14c0-3.5 2.2-6 5-6s5 2.5 5 6c0 3-1.8 5.5-4 6.5v2.5h-2v-2.5c-2.2-1-4-3.5-4-6.5z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M16 23h12v6H16z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M12 29h20v6c0 1.1-.9 2-2 2H14c-1.1 0-2-.9-2-2v-6z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <line x1="22" y1="12" x2="22" y2="18" stroke={isWhite ? '#B45309' : '#0F172A'} strokeWidth="1.2" />
            <line x1="19.5" y1="14.5" x2="24.5" y2="14.5" stroke={isWhite ? '#B45309' : '#0F172A'} strokeWidth="1.2" />
          </g>
        );

      case 'r': // Rook
        return (
          <g>
            <path
              d="M14 10h3v4h2v-4h2v4h2v-4h2v4h2v-4h3v6h-16v-6z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M16 16h12v12H16z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M12 28h20v7c0 1.1-.9 2-2 2H14c-1.1 0-2-.9-2-2v-7z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
          </g>
        );

      case 'q': // Queen
        return (
          <g>
            <circle cx="12" cy="11" r="1.8" fill={isWhite ? '#FDE047' : '#38BDF8'} />
            <circle cx="17" cy="9" r="1.8" fill={isWhite ? '#FDE047' : '#38BDF8'} />
            <circle cx="22" cy="8" r="2.2" fill={isWhite ? '#FDE047' : '#38BDF8'} />
            <circle cx="27" cy="9" r="1.8" fill={isWhite ? '#FDE047' : '#38BDF8'} />
            <circle cx="32" cy="11" r="1.8" fill={isWhite ? '#FDE047' : '#38BDF8'} />
            <path
              d="M13 13l3 8h12l3-8-4 5-5-7-5 7-4-5z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M15 21h14v7H15z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M11 28h22v7c0 1.1-.9 2-2 2H13c-1.1 0-2-.9-2-2v-7z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
          </g>
        );

      case 'k': // King
        return (
          <g>
            {/* Cross */}
            <path d="M21 5h2v6h-2zM19 7h6v2h-6z" fill={isWhite ? '#FACC15' : '#E2E8F0'} />
            {/* Crown */}
            <path
              d="M16 11c0 3 2 5.5 6 5.5s6-2.5 6-5.5l-2 3-4-2-4 2-2-3z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M15 16h14v10H15z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
            <path
              d="M11 26h22v9c0 1.1-.9 2-2 2H13c-1.1 0-2-.9-2-2v-9z"
              fill={isWhite ? 'url(#whitePieceGrad)' : 'url(#blackPieceGrad)'}
              stroke={isWhite ? '#D4AF37' : '#64748B'}
              strokeWidth="1.2"
            />
          </g>
        );
    }
  };

  return (
    <div
      className={`relative flex items-center justify-center transition-all duration-200 select-none ${
        isSelected ? 'scale-110 -translate-y-2' : 'hover:scale-105 hover:-translate-y-1'
      }`}
      style={{
        width: size,
        height: size,
        filter: isWhite
          ? 'drop-shadow(0 4px 6px rgba(0,0,0,0.45)) drop-shadow(0 1px 2px rgba(253,224,71,0.3))'
          : 'drop-shadow(0 4px 6px rgba(0,0,0,0.75)) drop-shadow(0 1px 2px rgba(56,189,248,0.25))'
      }}
    >
      <svg
        viewBox="0 0 44 44"
        width={size}
        height={size}
        className="w-full h-full overflow-visible"
      >
        <defs>
          {/* White Piece 3D Pearl Ivory Gradient */}
          <linearGradient id="whitePieceGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#FFFFFF" />
            <stop offset="45%" stopColor="#F8FAFC" />
            <stop offset="85%" stopColor="#E2E8F0" />
            <stop offset="100%" stopColor="#CBD5E1" />
          </linearGradient>

          {/* Black Piece 3D Obsidian Graphite Gradient */}
          <linearGradient id="blackPieceGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#334155" />
            <stop offset="40%" stopColor="#1E293B" />
            <stop offset="85%" stopColor="#0F172A" />
            <stop offset="100%" stopColor="#020617" />
          </linearGradient>
        </defs>

        {renderPath()}
      </svg>
    </div>
  );
};
