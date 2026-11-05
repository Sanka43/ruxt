import React, { useState, useMemo } from 'react';
import {
  RotateCcw,
  Volume2,
  VolumeX,
  Repeat,
  Zap,
  Target,
  Crown,
  ChevronRight,
  Sparkles,
  Camera,
  Activity,
  Award,
  BookOpen,
  ShieldAlert,
  ArrowRight
} from 'lucide-react';
import { ChessPiece, PieceColor, PieceType } from './chess/ChessPiece';
import {
  BoardState,
  INITIAL_BOARD,
  TAL_SACRIFICE_BOARD,
  LUCENA_BOARD,
  SMOTHERED_BOARD,
  GREEK_GIFT_BOARD,
  calculateMaterial,
  getLegalMoves,
  toAlgebraic,
  playMoveSound
} from './chess/chessUtils';

interface ChessCanvasProps {
  onMoveMade?: (move: string) => void;
}

type CameraPreset = '3d-dynamic' | '3d-iso' | '3d-player' | '2d-flat';
type CanvasTab = 'arena' | 'tactics' | 'openings' | 'analysis';

export const ChessCanvas: React.FC<ChessCanvasProps> = ({ onMoveMade }) => {
  const [board, setBoard] = useState<BoardState>(INITIAL_BOARD);
  const [boardHistory, setBoardHistory] = useState<BoardState[]>([INITIAL_BOARD]);
  const [selected, setSelected] = useState<[number, number] | null>(null);
  const [legalMoves, setLegalMoves] = useState<[number, number][]>([]);
  const [moveHistory, setMoveHistory] = useState<string[]>(['1. e4 e5', '2. Nf3 Nc6']);
  const [currentTier, setCurrentTier] = useState<'novice' | 'club' | 'master' | 'legend'>('legend');
  const [evalScore, setEvalScore] = useState<string>('+0.4');
  const [evalDetail, setEvalDetail] = useState<string>('Equality / Slight White Initiative');
  const [activeScenario, setActiveScenario] = useState<string>('Standard Opening');
  const [cameraAngle, setCameraAngle] = useState<CameraPreset>('3d-dynamic');
  const [activeTab, setActiveTab] = useState<CanvasTab>('arena');
  const [isFlipped, setIsFlipped] = useState<boolean>(false);
  const [soundEnabled, setSoundEnabled] = useState<boolean>(true);
  const [lastMoveSquares, setLastMoveSquares] = useState<[[number, number], [number, number]] | null>(null);
  const [currentTurn, setCurrentTurn] = useState<'w' | 'b'>('w');

  const [coachAdvice, setCoachAdvice] = useState<string>(
    'Legend Track Active: 3D Grandmaster board engaged. Identify White’s light-square bishop outpost on c4 and challenge the center.'
  );

  // Material statistics & captured pieces
  const material = useMemo(() => calculateMaterial(board), [board]);

  // Handle square clicks for piece movement
  const handleSquareClick = (r: number, c: number) => {
    // If piece is already selected
    if (selected) {
      const [fromR, fromC] = selected;

      // Clicked the same square -> deselect
      if (fromR === r && fromC === c) {
        setSelected(null);
        setLegalMoves([]);
        return;
      }

      const movingPiece = board[fromR][fromC];
      if (!movingPiece) {
        setSelected(null);
        setLegalMoves([]);
        return;
      }

      // Check if clicked square is a legal move
      const isLegal = legalMoves.some(([mr, mc]) => mr === r && mc === c);

      if (isLegal) {
        const isCapture = Boolean(board[r][c]);

        // Clone and update board
        const nextBoard = board.map((row) => [...row]);
        nextBoard[fromR][fromC] = null;
        nextBoard[r][c] = movingPiece;

        if (soundEnabled) {
          playMoveSound(isCapture);
        }

        const moveNotation = toAlgebraic(movingPiece, fromR, fromC, r, c, isCapture);
        setBoardHistory((prev) => [...prev, board]);
        setBoard(nextBoard);
        setLastMoveSquares([[fromR, fromC], [r, c]]);
        setSelected(null);
        setLegalMoves([]);
        setMoveHistory((prev) => [...prev, moveNotation]);
        setCurrentTurn((prev) => (prev === 'w' ? 'b' : 'w'));

        // Dynamic coaching evaluation reactions
        if (r === 3 && c === 4) {
          setCoachAdvice('Master move! Pushing to e5 stakes an uncompromising claim in the center.');
          setEvalScore('+0.2');
          setEvalDetail('Equal central tension');
        } else if (r === 2 && c === 5) {
          setCoachAdvice('Kingside knight development ready for castling. Excellent piece harmony.');
          setEvalScore('+0.6');
          setEvalDetail('White slight advantage in space');
        } else if (movingPiece.type === 'q') {
          setCoachAdvice('Queen activated! Watch out for minor piece harassment and tempo losses.');
          setEvalScore('-0.1');
          setEvalDetail('Tactical counterplay possible');
        } else if (isCapture) {
          setCoachAdvice(`Capture with ${moveNotation}! Material removed. Verify if recapture is forced.`);
          setEvalScore(material.diff >= 0 ? `+${material.diff + 1}.0` : `${material.diff - 1}.0`);
        } else {
          setCoachAdvice(`Move ${moveNotation} recorded. Evaluate king safety and central pawn breaks.`);
        }

        onMoveMade?.(`I played ${moveNotation} on the 3D board. Coach Elena, give me your Grandmaster tactical breakdown of this move.`);
        return;
      }

      // If clicked another own piece, select that instead
      const targetPiece = board[r][c];
      if (targetPiece && targetPiece.color === movingPiece.color) {
        setSelected([r, c]);
        setLegalMoves(getLegalMoves(r, c, board));
        return;
      }

      // Deselect if invalid square clicked
      setSelected(null);
      setLegalMoves([]);
    } else {
      // Select new piece
      const piece = board[r][c];
      if (piece) {
        setSelected([r, c]);
        setLegalMoves(getLegalMoves(r, c, board));
      }
    }
  };

  // Undo last move
  const handleUndo = () => {
    if (boardHistory.length > 1) {
      const prevBoard = boardHistory[boardHistory.length - 1];
      setBoard(prevBoard);
      setBoardHistory((prev) => prev.slice(0, prev.length - 1));
      setMoveHistory((prev) => prev.slice(0, prev.length - 1));
      setSelected(null);
      setLegalMoves([]);
      setLastMoveSquares(null);
      setCurrentTurn((prev) => (prev === 'w' ? 'b' : 'w'));
    }
  };

  // Load tactical scenarios
  const loadScenario = (type: 'standard' | 'tal' | 'lucena' | 'smothered' | 'greekgift') => {
    setSelected(null);
    setLegalMoves([]);
    setLastMoveSquares(null);

    if (type === 'standard') {
      setBoard(INITIAL_BOARD);
      setBoardHistory([INITIAL_BOARD]);
      setMoveHistory(['1. e4 e5', '2. Nf3 Nc6']);
      setActiveScenario('Standard Opening');
      setEvalScore('+0.4');
      setEvalDetail('Classical Opening Harmony');
      setCoachAdvice('Standard Opening Position loaded. Fight for central d4/e4 control and develop minor pieces.');
    } else if (type === 'tal') {
      setBoard(TAL_SACRIFICE_BOARD);
      setBoardHistory([TAL_SACRIFICE_BOARD]);
      setMoveHistory(['1. e4 c5', '2. Nf3 d6', '3. d4 cxd4', '4. Nxd4 Nf6', '5. Nc3 a6', '6. Bc4 e6']);
      setActiveScenario('Mikhail Tal Dynamic Sac');
      setEvalScore('+2.8');
      setEvalDetail('White explosive kingside initiative');
      setCoachAdvice('Mikhail Tal Scenario: Black king is trapped in the center. Look for piece sacrifices on e6 or f7!');
      onMoveMade?.('Loaded Mikhail Tal dynamic attack scenario. Coach Elena, explain how to initiate the kingside sacrifice here!');
    } else if (type === 'lucena') {
      setBoard(LUCENA_BOARD);
      setBoardHistory([LUCENA_BOARD]);
      setMoveHistory(['Rook Endgame Drill', 'White pawn on e7']);
      setActiveScenario('Lucena Bridge Building');
      setEvalScore('+5.0');
      setEvalDetail('Winning Lucena Technique Required');
      setCoachAdvice('Lucena Position: White must execute the "Bridge" maneuver: play ♖b4, check the king away and shield pawn promotion with ♖e4!');
      onMoveMade?.('Loaded Lucena Endgame drill. Coach Elena, show me step-by-step how to build the bridge and promote!');
    } else if (type === 'smothered') {
      setBoard(SMOTHERED_BOARD);
      setBoardHistory([SMOTHERED_BOARD]);
      setMoveHistory(['Tactical Trap', 'Knight ready on f6']);
      setActiveScenario('Smothered Mate Pattern');
      setEvalScore('#2');
      setEvalDetail('Forced Mate Sequence');
      setCoachAdvice('Smothered Mate Pattern: Check with ♘f7+, force king cornered, sacrifice Queen on g8 and seal mate with ♘f7#!');
      onMoveMade?.('Loaded Smothered Mate pattern. Coach, explain the beauty of sacrificing the Queen on g8!');
    } else if (type === 'greekgift') {
      setBoard(GREEK_GIFT_BOARD);
      setBoardHistory([GREEK_GIFT_BOARD]);
      setMoveHistory(['Classical Attack', 'Bxh7+ sacrifice ready']);
      setActiveScenario('Greek Gift Sacrifice (Bxh7+)');
      setEvalScore('+3.2');
      setEvalDetail('Decisive Kingside Assault');
      setCoachAdvice('Greek Gift Setup: White sacrifices the light-squared bishop with Bxh7+! Black king is dragged out into an inescapable mating net with Ng5+ and Qh5.');
      onMoveMade?.('Loaded Greek Gift Sacrifice. Coach Elena, demonstrate the Bxh7+ follow-up lines!');
    }
  };

  // Camera 3D Style Calculation
  const getCameraStyle = (): React.CSSProperties => {
    switch (cameraAngle) {
      case '3d-dynamic':
        return {
          transform: 'perspective(1100px) rotateX(28deg) rotateZ(-8deg)',
          transition: 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
        };
      case '3d-iso':
        return {
          transform: 'perspective(1200px) rotateX(45deg) rotateZ(-45deg)',
          transition: 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
        };
      case '3d-player':
        return {
          transform: 'perspective(900px) rotateX(24deg) rotateZ(0deg)',
          transition: 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
        };
      case '2d-flat':
      default:
        return {
          transform: 'none',
          transition: 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)'
        };
    }
  };

  const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

  return (
    <div className="flex flex-col h-full bg-[#0F1422] rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
      {/* Top Header Bar */}
      <div className="flex flex-wrap items-center justify-between px-4 py-2.5 bg-black/40 border-b border-white/5 gap-2">
        <div className="flex items-center space-x-3">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-700 flex items-center justify-center text-white shadow-lg shadow-amber-500/20 border border-amber-400/30">
            <Crown className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center space-x-2">
              <h2 className="text-sm font-bold text-white tracking-wide">Grandmaster 3D Arena</h2>
              <span className="px-2 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-[10px] text-indigo-300 font-semibold flex items-center space-x-1">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping" />
                <span>3D Engine Live</span>
              </span>
            </div>
            <p className="text-[11px] text-slate-400 flex items-center space-x-2">
              <span>Elena Voss</span>
              <span>•</span>
              <span className="text-amber-400 font-medium">GM Elo 2840</span>
              <span>•</span>
              <span className="text-emerald-400">Turn: {currentTurn === 'w' ? 'White' : 'Black'}</span>
            </p>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="flex items-center bg-black/60 rounded-xl p-1 border border-white/5 space-x-1">
          <button
            onClick={() => setActiveTab('arena')}
            className={`px-3 py-1 rounded-lg text-xs font-medium transition ${
              activeTab === 'arena' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            3D Arena
          </button>
          <button
            onClick={() => setActiveTab('tactics')}
            className={`px-3 py-1 rounded-lg text-xs font-medium transition ${
              activeTab === 'tactics' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            Tactics Drills
          </button>
          <button
            onClick={() => setActiveTab('openings')}
            className={`px-3 py-1 rounded-lg text-xs font-medium transition ${
              activeTab === 'openings' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            Openings
          </button>
          <button
            onClick={() => setActiveTab('analysis')}
            className={`px-3 py-1 rounded-lg text-xs font-medium transition ${
              activeTab === 'analysis' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            GM Metrics
          </button>
        </div>

        {/* Controls: Camera, Audio, Flip, Reset */}
        <div className="flex items-center space-x-1.5">
          {/* 3D Camera Angle Selector */}
          <div className="flex items-center bg-black/40 rounded-xl p-0.5 border border-white/5">
            <button
              onClick={() => setCameraAngle('3d-dynamic')}
              className={`px-2 py-1 rounded-lg text-[10px] font-semibold transition ${
                cameraAngle === '3d-dynamic' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
              }`}
              title="3D Dynamic Angled View"
            >
              3D Dynamic
            </button>
            <button
              onClick={() => setCameraAngle('3d-iso')}
              className={`px-2 py-1 rounded-lg text-[10px] font-semibold transition ${
                cameraAngle === '3d-iso' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
              }`}
              title="3D Isometric View"
            >
              Isometric
            </button>
            <button
              onClick={() => setCameraAngle('3d-player')}
              className={`px-2 py-1 rounded-lg text-[10px] font-semibold transition ${
                cameraAngle === '3d-player' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
              }`}
              title="Player 3D Frontal View"
            >
              Frontal
            </button>
            <button
              onClick={() => setCameraAngle('2d-flat')}
              className={`px-2 py-1 rounded-lg text-[10px] font-semibold transition ${
                cameraAngle === '2d-flat' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
              }`}
              title="Classic 2D Flat View"
            >
              2D Flat
            </button>
          </div>

          {/* Sound Toggle */}
          <button
            onClick={() => setSoundEnabled((prev) => !prev)}
            className={`p-1.5 rounded-xl border transition ${
              soundEnabled
                ? 'bg-white/5 border-white/10 text-slate-300 hover:text-white'
                : 'bg-red-500/10 border-red-500/20 text-red-400'
            }`}
            title={soundEnabled ? 'Mute Move Sounds' : 'Unmute Move Sounds'}
          >
            {soundEnabled ? <Volume2 className="w-3.5 h-3.5" /> : <VolumeX className="w-3.5 h-3.5" />}
          </button>

          {/* Flip Board */}
          <button
            onClick={() => setIsFlipped((prev) => !prev)}
            className="p-1.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:text-white transition"
            title="Flip Board Perspective"
          >
            <Repeat className="w-3.5 h-3.5" />
          </button>

          {/* Undo Move */}
          <button
            onClick={handleUndo}
            disabled={boardHistory.length <= 1}
            className={`p-1.5 rounded-xl border transition ${
              boardHistory.length > 1
                ? 'bg-white/5 border-white/10 text-slate-300 hover:text-white'
                : 'bg-white/[0.02] border-transparent text-slate-600 cursor-not-allowed'
            }`}
            title="Undo last move"
          >
            <RotateCcw className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col overflow-y-auto p-4 space-y-3.5">
        {activeTab === 'arena' && (
          <div className="flex-1 flex flex-col xl:flex-row items-center justify-center gap-6 py-2">
            {/* 3D Chessboard Stage */}
            <div className="relative flex flex-col items-center">
              {/* Opponent Captured Pieces Bar */}
              <div className="w-full max-w-[420px] flex items-center justify-between px-3 py-1.5 mb-2 bg-black/40 rounded-xl border border-white/5 text-xs">
                <div className="flex items-center space-x-1.5">
                  <span className="text-[10px] uppercase font-bold text-slate-400">Black Captured:</span>
                  <div className="flex items-center space-x-0.5">
                    {material.capturedBlackPieces.map((p, idx) => (
                      <span key={idx} className="opacity-90 scale-75 origin-left">
                        <ChessPiece type={p} color="b" size={18} />
                      </span>
                    ))}
                    {material.capturedBlackPieces.length === 0 && (
                      <span className="text-[10px] text-slate-600 italic">None</span>
                    )}
                  </div>
                </div>
                {material.diff < 0 && (
                  <span className="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                    +{Math.abs(material.diff)} Black
                  </span>
                )}
              </div>

              {/* 3D Board Container */}
              <div
                className="relative p-4 md:p-6 rounded-3xl bg-gradient-to-b from-[#1E2538] via-[#141B2D] to-[#0A0E1A] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.95)] border-4 border-[#2D3748]/70"
                style={{
                  perspective: '1200px',
                  boxShadow: '0 30px 60px -12px rgba(0,0,0,0.9), inset 0 2px 5px rgba(255,255,255,0.15)'
                }}
              >
                {/* 3D Board Frame with Realistic Coordinates */}
                <div
                  className="relative transition-all duration-500 select-none"
                  style={getCameraStyle()}
                >
                  {/* Subtle 3D Depth Layer beneath the board */}
                  <div className="absolute inset-0 translate-y-3.5 translate-x-2 bg-black/70 rounded-2xl blur-md" />

                  {/* Chessboard Grid */}
                  <div
                    className="relative grid grid-cols-8 grid-rows-8 w-72 sm:w-84 md:w-96 aspect-square rounded-2xl overflow-hidden border-2 border-amber-600/30 shadow-2xl"
                    style={{
                      background: 'linear-gradient(135deg, #1A1F2C 0%, #10141E 100%)',
                      boxShadow: 'inset 0 0 15px rgba(0,0,0,0.8), 0 12px 25px rgba(0,0,0,0.7)'
                    }}
                  >
                    {(isFlipped ? [7, 6, 5, 4, 3, 2, 1, 0] : [0, 1, 2, 3, 4, 5, 6, 7]).map((r) =>
                      (isFlipped ? [7, 6, 5, 4, 3, 2, 1, 0] : [0, 1, 2, 3, 4, 5, 6, 7]).map((c) => {
                        const isDark = (r + c) % 2 === 1;
                        const isSelected = Boolean(selected && selected[0] === r && selected[1] === c);
                        const isLegalTarget = legalMoves.some(([mr, mc]) => mr === r && mc === c);
                        const isLastMove =
                          lastMoveSquares &&
                          ((lastMoveSquares[0][0] === r && lastMoveSquares[0][1] === c) ||
                            (lastMoveSquares[1][0] === r && lastMoveSquares[1][1] === c));
                        const piece = board[r][c];

                        return (
                          <div
                            key={`${r}-${c}`}
                            onClick={() => handleSquareClick(r, c)}
                            className={`relative flex items-center justify-center cursor-pointer transition-all duration-150 ${
                              isDark
                                ? 'bg-gradient-to-br from-[#4A7C59] to-[#3B6447]'
                                : 'bg-gradient-to-br from-[#EAE8D4] to-[#D8D4BC]'
                            } ${
                              isSelected
                                ? 'ring-4 ring-amber-400 ring-inset z-20 brightness-110'
                                : isLastMove
                                ? 'bg-amber-400/30 ring-2 ring-amber-400/40 ring-inset'
                                : 'hover:brightness-105'
                            }`}
                          >
                            {/* File / Rank Coordinate Labels inside corner squares */}
                            {c === (isFlipped ? 7 : 0) && (
                              <span
                                className={`absolute top-0.5 left-1 text-[8px] font-bold font-mono select-none pointer-events-none ${
                                  isDark ? 'text-emerald-100/60' : 'text-stone-600/70'
                                }`}
                              >
                                {8 - r}
                              </span>
                            )}
                            {r === (isFlipped ? 0 : 7) && (
                              <span
                                className={`absolute bottom-0.5 right-1 text-[8px] font-bold font-mono select-none pointer-events-none ${
                                  isDark ? 'text-emerald-100/60' : 'text-stone-600/70'
                                }`}
                              >
                                {files[c]}
                              </span>
                            )}

                            {/* Legal Move Indicator Dot */}
                            {isLegalTarget && !piece && (
                              <div className="absolute w-3.5 h-3.5 rounded-full bg-amber-400/70 shadow-lg shadow-amber-400/50 ring-2 ring-white/60 animate-pulse pointer-events-none" />
                            )}

                            {/* Legal Capture Target Ring */}
                            {isLegalTarget && piece && (
                              <div className="absolute inset-0 rounded-none border-4 border-red-500/70 bg-red-500/20 animate-pulse pointer-events-none" />
                            )}

                            {/* 3D Chess Piece */}
                            {piece && (
                              <div className="relative z-10">
                                <ChessPiece
                                  type={piece.type}
                                  color={piece.color}
                                  isSelected={isSelected}
                                  size={40}
                                />
                              </div>
                            )}
                          </div>
                        );
                      })
                    )}
                  </div>
                </div>
              </div>

              {/* Player Captured Pieces Bar */}
              <div className="w-full max-w-[420px] flex items-center justify-between px-3 py-1.5 mt-2 bg-black/40 rounded-xl border border-white/5 text-xs">
                <div className="flex items-center space-x-1.5">
                  <span className="text-[10px] uppercase font-bold text-slate-400">White Captured:</span>
                  <div className="flex items-center space-x-0.5">
                    {material.capturedWhitePieces.map((p, idx) => (
                      <span key={idx} className="opacity-90 scale-75 origin-left">
                        <ChessPiece type={p} color="w" size={18} />
                      </span>
                    ))}
                    {material.capturedWhitePieces.length === 0 && (
                      <span className="text-[10px] text-slate-600 italic">None</span>
                    )}
                  </div>
                </div>
                {material.diff > 0 && (
                  <span className="text-[10px] font-bold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/20">
                    +{material.diff} White
                  </span>
                )}
              </div>
            </div>

            {/* Sidebar Controls & Game Intelligence */}
            <div className="w-full xl:w-72 2xl:w-80 flex flex-col space-y-3 self-stretch">
              {/* Dynamic Position Evaluation Bar */}
              <div className="bg-black/40 rounded-2xl p-3.5 border border-white/5 space-y-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-1.5">
                    <Activity className="w-4 h-4 text-amber-400" />
                    <span className="text-xs font-semibold text-slate-300">Neural Stockfish Eval</span>
                  </div>
                  <span className="font-mono font-bold text-xs text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                    {evalScore}
                  </span>
                </div>

                {/* Progress bar visualizer */}
                <div className="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden p-0.5 border border-white/5">
                  <div
                    className="h-full bg-gradient-to-r from-indigo-500 via-amber-400 to-emerald-400 rounded-full transition-all duration-500"
                    style={{
                      width: `${Math.min(95, Math.max(10, 50 + parseFloat(evalScore.replace('#', '10')) * 8))}%`
                    }}
                  />
                </div>
                <div className="flex items-center justify-between text-[10px] text-slate-400">
                  <span>Depth 26 Plies</span>
                  <span className="italic">{evalDetail}</span>
                </div>
              </div>

              {/* Move Notation Log */}
              <div className="bg-black/35 rounded-2xl p-3.5 border border-white/5 flex-1 flex flex-col min-h-[130px]">
                <div className="flex items-center justify-between pb-2 border-b border-white/5 mb-2">
                  <span className="text-xs font-semibold text-slate-300 flex items-center space-x-1.5">
                    <BookOpen className="w-3.5 h-3.5 text-indigo-400" />
                    <span>Move Log ({moveHistory.length})</span>
                  </span>
                  <span className="text-[10px] text-slate-500 font-mono">PGN Format</span>
                </div>

                <div className="flex-1 overflow-y-auto space-y-1 pr-1 max-h-32 xl:max-h-44">
                  {moveHistory.map((m, idx) => (
                    <div
                      key={idx}
                      className="flex items-center justify-between text-xs py-1 px-2 rounded-lg hover:bg-white/5 transition border-b border-white/[0.03]"
                    >
                      <span className="text-slate-500 font-mono text-[11px]">{idx + 1}.</span>
                      <span className="font-mono font-semibold text-indigo-300">{m}</span>
                      <span className="text-[10px] text-emerald-400 font-mono">OK</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Quick GM Dispatch Prompts (sends to chat right away) */}
              <div className="space-y-1.5">
                <span className="text-[10px] uppercase font-bold text-slate-400 px-1 block">
                  Quick Grandmaster Queries
                </span>
                <button
                  onClick={() =>
                    onMoveMade?.(
                      `Coach Elena, please provide a deep Grandmaster evaluation of this position and outline the top 3 candidate moves for White.`
                    )
                  }
                  className="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 text-xs font-medium transition"
                >
                  <span className="flex items-center space-x-2">
                    <Sparkles className="w-3.5 h-3.5 text-indigo-400" />
                    <span>Evaluate & Next 3 Candidate Moves</span>
                  </span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>

                <button
                  onClick={() =>
                    onMoveMade?.(
                      `Coach Elena, what tactical motif or sacrifice intuition should I calculate right here to achieve Mikhail Tal mastery?`
                    )
                  }
                  className="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-amber-600/20 hover:bg-amber-600/30 border border-amber-500/30 text-amber-300 text-xs font-medium transition"
                >
                  <span className="flex items-center space-x-2">
                    <Target className="w-3.5 h-3.5 text-amber-400" />
                    <span>Drill Mikhail Tal Tactical Combination</span>
                  </span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Tab 2: Tactics & Legendary Scenarios */}
        {activeTab === 'tactics' && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div
              onClick={() => {
                loadScenario('tal');
                setActiveTab('arena');
              }}
              className="p-4 rounded-2xl bg-amber-950/20 hover:bg-amber-950/30 border border-amber-500/30 cursor-pointer transition space-y-2 group"
            >
              <div className="flex items-center justify-between">
                <span className="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold">
                  Dynamic Sacrifice
                </span>
                <ArrowRight className="w-4 h-4 text-amber-400 group-hover:translate-x-1 transition" />
              </div>
              <h4 className="text-sm font-bold text-white">Mikhail Tal Kingside Assault</h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                Black's king is stranded in the center. Uncover explosive piece sacrifices on e6 or f7 to shred the pawn shelter.
              </p>
            </div>

            <div
              onClick={() => {
                loadScenario('lucena');
                setActiveTab('arena');
              }}
              className="p-4 rounded-2xl bg-indigo-950/20 hover:bg-indigo-950/30 border border-indigo-500/30 cursor-pointer transition space-y-2 group"
            >
              <div className="flex items-center justify-between">
                <span className="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-bold">
                  Endgame Mastery
                </span>
                <ArrowRight className="w-4 h-4 text-indigo-400 group-hover:translate-x-1 transition" />
              </div>
              <h4 className="text-sm font-bold text-white">Lucena Bridge Technique</h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                Master the single most important rook endgame in chess. Execute the 4th-rank rook maneuver to shield promotion.
              </p>
            </div>

            <div
              onClick={() => {
                loadScenario('smothered');
                setActiveTab('arena');
              }}
              className="p-4 rounded-2xl bg-purple-950/20 hover:bg-purple-950/30 border border-purple-500/30 cursor-pointer transition space-y-2 group"
            >
              <div className="flex items-center justify-between">
                <span className="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold">
                  Tactical Pattern
                </span>
                <ArrowRight className="w-4 h-4 text-purple-400 group-hover:translate-x-1 transition" />
              </div>
              <h4 className="text-sm font-bold text-white">Smothered Mate Pattern</h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                Check with ♘f7+, sacrifice Queen on g8 to suffocate the enemy king behind their own captured pieces.
              </p>
            </div>

            <div
              onClick={() => {
                loadScenario('greekgift');
                setActiveTab('arena');
              }}
              className="p-4 rounded-2xl bg-emerald-950/20 hover:bg-emerald-950/30 border border-emerald-500/30 cursor-pointer transition space-y-2 group"
            >
              <div className="flex items-center justify-between">
                <span className="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">
                  Mating Net
                </span>
                <ArrowRight className="w-4 h-4 text-emerald-400 group-hover:translate-x-1 transition" />
              </div>
              <h4 className="text-sm font-bold text-white">Greek Gift Sacrifice (Bxh7+)</h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                Classic light-squared bishop sacrifice that drags Black's castled king out into a lethal fork or direct mating sequence.
              </p>
            </div>
          </div>
        )}

        {/* Tab 3: Grandmaster Openings */}
        {activeTab === 'openings' && (
          <div className="space-y-3">
            <div className="p-3.5 bg-black/30 rounded-xl border border-white/5 flex items-center justify-between">
              <div>
                <h4 className="text-xs font-bold text-white">Sicilian Defense (Najdorf 5... a6)</h4>
                <p className="text-[11px] text-slate-400">1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 a6</p>
              </div>
              <button
                onClick={() => {
                  loadScenario('tal');
                  setActiveTab('arena');
                }}
                className="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 text-xs font-medium border border-indigo-500/30 transition"
              >
                Load Line
              </button>
            </div>

            <div className="p-3.5 bg-black/30 rounded-xl border border-white/5 flex items-center justify-between">
              <div>
                <h4 className="text-xs font-bold text-white">Queen's Gambit Declined (Orthodox)</h4>
                <p className="text-[11px] text-slate-400">1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Bg5 Be7 5. e3 O-O</p>
              </div>
              <button
                onClick={() => {
                  loadScenario('standard');
                  setActiveTab('arena');
                }}
                className="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 text-xs font-medium border border-indigo-500/30 transition"
              >
                Load Line
              </button>
            </div>

            <div className="p-3.5 bg-black/30 rounded-xl border border-white/5 flex items-center justify-between">
              <div>
                <h4 className="text-xs font-bold text-white">Ruy Lopez (Closed Morphy Defense)</h4>
                <p className="text-[11px] text-slate-400">1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7</p>
              </div>
              <button
                onClick={() => {
                  loadScenario('standard');
                  setActiveTab('arena');
                }}
                className="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 text-xs font-medium border border-indigo-500/30 transition"
              >
                Load Line
              </button>
            </div>
          </div>
        )}

        {/* Tab 4: Grandmaster Metrics & Diagnostics */}
        {activeTab === 'analysis' && (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div className="p-3.5 bg-black/40 rounded-xl border border-white/5 space-y-1">
              <span className="text-[10px] text-slate-400 uppercase font-semibold">Tactical Accuracy</span>
              <div className="text-xl font-bold font-mono text-emerald-400">96.8%</div>
              <p className="text-[10px] text-slate-500">Master Level precision</p>
            </div>
            <div className="p-3.5 bg-black/40 rounded-xl border border-white/5 space-y-1">
              <span className="text-[10px] text-slate-400 uppercase font-semibold">King Safety Index</span>
              <div className="text-xl font-bold font-mono text-indigo-400">92 / 100</div>
              <p className="text-[10px] text-slate-500">Kingside shield intact</p>
            </div>
            <div className="p-3.5 bg-black/40 rounded-xl border border-white/5 space-y-1">
              <span className="text-[10px] text-slate-400 uppercase font-semibold">Blunder Risk</span>
              <div className="text-xl font-bold font-mono text-amber-400">&lt; 1.4%</div>
              <p className="text-[10px] text-slate-500">Low tactical vulnerability</p>
            </div>
            <div className="p-3.5 bg-black/40 rounded-xl border border-white/5 space-y-1">
              <span className="text-[10px] text-slate-400 uppercase font-semibold">Central Space Control</span>
              <div className="text-xl font-bold font-mono text-purple-400">+22%</div>
              <p className="text-[10px] text-slate-500">e4/d4 squares dominated</p>
            </div>
          </div>
        )}
      </div>

      {/* Elena Voss Live Coach Feedback Card */}
      <div className="p-3 bg-gradient-to-r from-indigo-950/70 via-[#111827] to-amber-950/40 border-t border-white/10 flex items-center justify-between gap-3">
        <div className="flex items-center space-x-2.5 min-w-0">
          <div className="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-lg shrink-0">
            ♟️
          </div>
          <div className="min-w-0">
            <div className="flex items-center space-x-1.5">
              <span className="text-xs font-bold text-amber-300">Elena Voss Grandmaster Assessment</span>
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            </div>
            <p className="text-xs text-slate-200 truncate">{coachAdvice}</p>
          </div>
        </div>

        <button
          onClick={() =>
            onMoveMade?.(
              `Coach Elena, explain this specific position in deep tactical detail and recommend my next move.`
            )
          }
          className="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-bold shadow-lg shadow-amber-500/20 shrink-0 transition"
        >
          Ask Coach to Explain
        </button>
      </div>
    </div>
  );
};
