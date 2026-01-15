<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Configuration Export</title>
    <style>
        @page {
            margin: 100px 40px 60px 40px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        /* Header on every page */
        .header {
            position: running(header);
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #333;
        }
        
        .logo-space {
            height: 40px;
            background: #f5f5f5;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            margin-bottom: 5px;
            font-size: 9pt;
        }
        
        .header h1 {
            font-size: 14pt;
            margin-bottom: 2px;
        }
        
        .header .subtitle {
            font-size: 9pt;
            color: #666;
        }
        
        /* Footer on every page */
        .footer {
            position: running(footer);
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Page content */
        .content {
            padding: 0;
        }
        
        /* Selection Summary */
        .summary-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .summary-section h2 {
            font-size: 13pt;
            margin-bottom: 12px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .iso-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        
        .iso-table th,
        .iso-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        .iso-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .iso-table .class-number {
            font-size: 11pt;
            font-weight: bold;
        }
        
        .iso-table .description {
            font-size: 8pt;
            color: #666;
            white-space: pre-line;
        }
        
        .summary-details {
            margin-top: 10px;
            font-size: 9pt;
        }
        
        .summary-details div {
            margin-bottom: 4px;
        }
        
        .summary-details strong {
            font-weight: bold;
        }
        
        /* Configuration Visual */
        .configuration-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .configuration-section h2 {
            font-size: 13pt;
            margin-bottom: 12px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .config-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        /* Configuration Details with Vertical Flow */
        .parts-section {
            margin-top: 20px;
        }
        
        .parts-section h2 {
            font-size: 13pt;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .config-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }
        
        .config-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .config-flow-range {
            font-size: 9pt;
            color: #666;
        }
        
        .parts-flow {
            position: relative;
            padding-left: 20px;
        }
        
        /* Each item (except last) draws a line from its center to next item */
        .part-item .part-image::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 100%;
            width: 2px;
            background: #999;
            /* Line goes down to where next item would be (generous estimate) */
            height: 200px;
            z-index: 0;
        }
        
        /* Last item doesn't draw a line */
        .part-item:last-child .part-image::before {
            display: none;
        }
        
        .part-item {
            position: relative;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            page-break-inside: avoid;
        }
        
        .part-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .part-header {
            display: table;
            width: 100%;
        }
        
        .part-image-container {
            display: table-cell;
            vertical-align: middle;
            width: 140px;
            position: relative;
        }
        
        .part-image {
            width: 120px;
            height: 120px;
            border: 2px solid #ddd;
            border-radius: 4px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            position: relative;
            z-index: 1;
        }
        
        .part-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .part-image .no-image {
            color: #999;
            font-size: 8pt;
        }
        
        .part-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }
        
        .part-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .part-description {
            font-size: 9pt;
            color: #555;
            white-space: pre-line;
            margin-bottom: 8px;
        }
        
        .part-notes {
            margin-top: 8px;
        }
        
        .note-box {
            margin-bottom: 6px;
            padding: 6px;
            border-radius: 3px;
            font-size: 8pt;
            page-break-inside: avoid;
        }
        
        .note-box.refrigerant {
            background: #e3f2fd;
            border: 1px solid #90caf9;
        }
        
        .note-box.desiccant {
            background: #f3e5f5;
            border: 1px solid #ce93d8;
        }
        
        .note-box.qaf {
            background: #e8f5e9;
            border: 1px solid #81c784;
        }
        
        .note-label {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .note-text {
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <!-- Header on every page -->
    <div class="header">
        <div class="logo-space">Logo Placeholder</div>
        <h1>Air Compressor Configuration</h1>
        <div class="subtitle">Quality Air System Configuration Report</div>
    </div>
    
    <!-- Footer with page numbers on every page -->
    <div class="footer">
        <script type="text/php">
            if (isset($pdf)) {
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $font = $fontMetrics->get_font("Arial");
                $size = 9;
                $pageText = $text;
                $y = 820;
                $x = 520;
                $pdf->text($x, $y, $pageText, $font, $size);
            }
        </script>
    </div>
    
    <div class="content">
        <!-- Selection Summary -->
        <div class="summary-section">
            <h2>Selection Summary</h2>
            
            <table class="iso-table">
                <thead>
                    <tr>
                        <th>Particulate</th>
                        <th>Water</th>
                        <th>Oil</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="class-number">{{ $input['particulate_class'] }}</td>
                        <td class="class-number">{{ $input['water_class'] }}</td>
                        <td class="class-number">{{ $input['oil_class'] }}</td>
                    </tr>
                    <tr>
                        <td class="description">{{ $purityDescriptions['particulate'] ?? '' }}</td>
                        <td class="description">{{ $purityDescriptions['water'] ?? '' }}</td>
                        <td class="description">{{ $purityDescriptions['oil'] ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="summary-details">
                @if(!empty($input['preset']))
                    <div><strong>Industry/Application:</strong> {{ $input['preset'] }}</div>
                @endif
                <div><strong>Flow:</strong> {{ $input['flow'] ? $input['flow'] . ' CFM' : 'All Ranges' }}</div>
            </div>
        </div>
        
        <!-- Configuration Details with Vertical Flow -->
        <div class="parts-section">
            <h2>Selected Configuration</h2>
            
            <div class="parts-flow">
            <!-- Compressor Details -->
            <div class="part-item">
                <div class="part-header">
                    <div class="part-image-container">
                        <div class="part-image">
                            @if(!empty($configuration['compressor_image']))
                                <img src="{{ $configuration['compressor_image'] }}" alt="{{ $configuration['compressor'] }}">
                            @else
                                <div class="no-image">No Image Available</div>
                            @endif
                        </div>
                    </div>
                    <div class="part-info">
                        <div class="part-name">{{ $configuration['compressor'] }}</div>
                        @if(!empty($configuration['compressor_description']))
                            <div class="part-description">{{ $configuration['compressor_description'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Component Details -->
            @foreach($configuration['components'] as $component)
                <div class="part-item">
                    <div class="part-header">
                        <div class="part-image-container">
                            <div class="part-image">
                                @if(!empty($component['image']))
                                    <img src="{{ $component['image'] }}" alt="{{ $component['name'] }}">
                                @else
                                    <div class="no-image">No Image Available</div>
                                @endif
                            </div>
                        </div>
                        <div class="part-info">
                            <div class="part-name">{{ $component['name'] }}</div>
                            
                            @if(!empty($component['is_dryer']))
                                <div style="font-size: 9pt; color: #666; margin-bottom: 8px;">
                                    <strong>Flow Range:</strong> {{ $component['flow_range'] }} CFM
                                    @if(!empty($component['dewpoint']))
                                        <br><strong>Dewpoint:</strong> {{ $component['dewpoint'] }}
                                    @endif
                                </div>
                            @endif
                            
                            @if(!empty($component['description']))
                                <div class="part-description">{{ $component['description'] }}</div>
                            @endif
                            
                            <!-- Notes -->
                            @if(!empty($component['refrigerant_dryer_note']) || !empty($component['desiccant_dryer_note']) || !empty($component['qaf_note']))
                                <div class="part-notes">
                                    @if(!empty($component['refrigerant_dryer_note']))
                                        <div class="note-box refrigerant">
                                            <div class="note-label">Refrigerant Dryer Note:</div>
                                            <div class="note-text">{{ $component['refrigerant_dryer_note'] }}</div>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($component['desiccant_dryer_note']))
                                        <div class="note-box desiccant">
                                            <div class="note-label">Desiccant Dryer Note:</div>
                                            <div class="note-text">{{ $component['desiccant_dryer_note'] }}</div>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($component['qaf_note']))
                                        <div class="note-box qaf">
                                            <div class="note-label">QAF Note:</div>
                                            <div class="note-text">{{ $component['qaf_note'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>
</body>
</html>