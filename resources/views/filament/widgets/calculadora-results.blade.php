<div>
    @if(isset($resultado['error']))
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; color: #991b1b;">
            <strong>Error:</strong> {{ $resultado['error'] }}
        </div>
    @elseif($resultado['success'])
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
            <!-- Piezas por hoja -->
            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #166534; margin-bottom: 4px;">
                    {{ $resultado['piezasPorHoja'] }}
                </div>
                <div style="font-size: 14px; color: #15803d; font-weight: 500;">Piezas por Hoja</div>
            </div>

            <!-- Hojas necesarias -->
            <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 16px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #1e40af; margin-bottom: 4px;">
                    {{ $resultado['hojasNecesarias'] }}
                </div>
                <div style="font-size: 14px; color: #1d4ed8; font-weight: 500;">Hojas Necesarias</div>
            </div>

            <!-- Eficiencia -->
            <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #92400e; margin-bottom: 4px;">
                    {{ $resultado['eficiencia'] }}%
                </div>
                <div style="font-size: 14px; color: #b45309; font-weight: 500;">Eficiencia</div>
            </div>
        </div>

        <!-- Detalles adicionales -->
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div>
                    <span style="font-size: 13px; color: #6b7280;">Piezas Obtenidas:</span>
                    <span style="font-size: 15px; color: #111827; font-weight: 600; margin-left: 8px;">{{ $resultado['piezasObtenidas'] }}</span>
                </div>
                <div>
                    <span style="font-size: 13px; color: #6b7280;">Piezas Sobrantes:</span>
                    <span style="font-size: 15px; color: #111827; font-weight: 600; margin-left: 8px;">{{ $resultado['piezasSobrantes'] }}</span>
                </div>
                <div>
                    <span style="font-size: 13px; color: #6b7280;">Cortes Horizontales:</span>
                    <span style="font-size: 15px; color: #111827; font-weight: 600; margin-left: 8px;">{{ $resultado['cortesHorizontales'] }}</span>
                </div>
                <div>
                    <span style="font-size: 13px; color: #6b7280;">Cortes Verticales:</span>
                    <span style="font-size: 15px; color: #111827; font-weight: 600; margin-left: 8px;">{{ $resultado['cortesVerticales'] }}</span>
                </div>
            </div>
        </div>

        <!-- Visualización SVG -->
        <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center;">
            <h4 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Vista Previa del Corte</h4>
            @php
                $paperWidth = min($resultado['anchoPapel'], $resultado['largoPapel']);
                $paperHeight = max($resultado['anchoPapel'], $resultado['largoPapel']);
                $cutWidth = $resultado['anchoCorte'];
                $cutHeight = $resultado['largoCorte'];
                $cortesH = $resultado['cortesHorizontales'];
                $cortesV = $resultado['cortesVerticales'];

                $maxSvgWidth = 600;
                $scale = $maxSvgWidth / max($paperWidth, $paperHeight);
                $svgWidth = $paperWidth * $scale;
                $svgHeight = $paperHeight * $scale;

                $pieceWidth = $cutWidth;
                $pieceHeight = $cutHeight;
                $pieceWidthScaled = $pieceWidth * $scale;
                $pieceHeightScaled = $pieceHeight * $scale;
            @endphp

            <svg width="{{ $svgWidth }}" height="{{ $svgHeight }}" viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" xmlns="http://www.w3.org/2000/svg" style="border: 2px solid #9ca3af; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 0 auto; display: block;">
                <!-- Fondo del papel -->
                <rect x="0" y="0" width="{{ $svgWidth }}" height="{{ $svgHeight }}" fill="#dbeafe" stroke="#3b82f6" stroke-width="2"/>

                <!-- Piezas -->
                @php $pieceNumber = 1; @endphp
                @for ($row = 0; $row < $cortesV; $row++)
                    @for ($col = 0; $col < $cortesH; $col++)
                        @php
                            $x = $col * $pieceWidthScaled;
                            $y = $row * $pieceHeightScaled;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $pieceWidthScaled }}" height="{{ $pieceHeightScaled }}"
                              fill="#86efac" stroke="#16a34a" stroke-width="1.5" rx="2" opacity="0.85"/>
                        @if ($pieceWidthScaled > 15 && $pieceHeightScaled > 15)
                            <text x="{{ $x + $pieceWidthScaled / 2 }}" y="{{ $y + $pieceHeightScaled / 2 }}"
                                  font-size="10" fill="#166534" font-weight="bold" text-anchor="middle" dominant-baseline="middle">{{ $pieceNumber }}</text>
                        @endif
                        @php $pieceNumber++; @endphp
                    @endfor
                @endfor

                <!-- Dimensiones del papel -->
                <text x="{{ $svgWidth / 2 }}" y="15" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle">{{ $paperWidth }}cm</text>
                <text x="15" y="{{ $svgHeight / 2 }}" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle" transform="rotate(-90 15 {{ $svgHeight / 2 }})">{{ $paperHeight }}cm</text>

                <!-- Info inferior -->
                <text x="{{ $svgWidth / 2 }}" y="{{ $svgHeight - 10 }}" font-size="11" fill="#374151" text-anchor="middle">{{ $resultado['piezasPorHoja'] }} piezas | {{ $resultado['eficiencia'] }}% eficiencia</text>
            </svg>
        </div>
    @endif
</div>
