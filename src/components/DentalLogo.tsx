/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

interface DentalLogoProps {
  className?: string;
  showSubtitle?: boolean;
}

export default function DentalLogo({ className = "h-10", showSubtitle = true }: DentalLogoProps) {
  return (
    <div className={`flex items-center gap-3 ${className}`}>
      {/* Handcrafted tooth SVG styled close to the Goshen Dental theme */}
      <svg
        id="goshen_logo_svg"
        viewBox="0 0 200 180"
        className="w-12 h-12 text-blue-600 drop-shadow-sm flex-shrink-0"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <linearGradient id="toothGradient" x1="0" y1="0" x2="200" y2="180" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stopColor="#312e81" /> {/* Indigo 900 */}
            <stop offset="50%" stopColor="#4f46e5" /> {/* Indigo 600 */}
            <stop offset="100%" stopColor="#6366f1" /> {/* Indigo 500 */}
          </linearGradient>
        </defs>
        
        {/* Artistic abstraction of a molar tooth with custom swoops similar to Goshen branding */}
        <path
          d="M 40,45 
             C 40,15 95,12 100,40 
             C 105,12 160,15 160,45 
             C 160,85 150,115 138,155 
             C 134,168 118,172 110,160 
             C 105,152 102,130 100,130 
             C 98,130 95,152 90,160 
             C 82,172 66,168 62,155 
             C 50,115 40,85 40,45 Z"
          stroke="url(#toothGradient)"
          strokeWidth="11"
          strokeLinecap="round"
          strokeLinejoin="round"
          fill="none"
        />
        
        {/* Swooping overlay lines representing the gum line structure and beautiful smiles */}
        <path
          d="M 60,65 C 80,85 120,85 140,65"
          stroke="#6366f1"
          strokeWidth="4"
          strokeLinecap="round"
          fill="none"
        />
        
        <path
          d="M 80,105 C 90,112 110,112 120,105"
          stroke="#94a3b8"
          strokeWidth="3"
          strokeLinecap="round"
          fill="none"
        />
      </svg>

      <div className="flex flex-col justify-center">
        <h1 className="text-xl font-bold text-slate-800 tracking-tight font-sans flex items-baseline">
          Goshen
          <span className="text-indigo-600 font-semibold text-xs uppercase tracking-widest ml-1.5 border-l border-slate-300 pl-1.5">
            Dental care
          </span>
        </h1>
        {showSubtitle && (
          <span className="text-[10px] uppercase font-mono text-slate-400 tracking-[0.25em] font-medium leading-none mt-1">
            Creating Perfect Smiles
          </span>
        )}
      </div>
    </div>
  );
}
