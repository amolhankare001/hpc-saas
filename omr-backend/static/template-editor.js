// ========== YOMARK-STYLE TEMPLATE EDITOR ==========
// Complete interactive template editor with image loading,
// pivot placement, OMR region drawing, barcode region drawing,
// and properties panel - matching Yoctel/Yomark software exactly.

// === EDITOR STATE ===
let editorMode = 'select'; // select, pivot, omr, barcode, masterpivot
let editorImage = null;      // loaded Image object
let editorImageDataURL = null; // base64 data URL of loaded image
let editorRegions = [];       // array of region objects
let selectedRegionIdx = -1;   // index of selected region
let pivotCount = 0;
let drawStart = null;         // {x, y} for click-drag start (normalized 0-1)
let isDragging = false;
let isResizing = false;
let resizeHandle = null;      // which handle is being dragged
let isMoving = false;
let moveOffset = {x: 0, y: 0};
let editorCanvasW = 0;
let editorCanvasH = 0;
let showLabels = true;
let lowContrast = false;
let showNoise = false;
let omrRegionCount = 0;
let barcodeRegionCount = 0;

// === REGION DATA STRUCTURES (matching Yomark exactly) ===
// Pivot: { type:'pivot', name:'PIVOT1', x, y (normalized 0-1 center), isMaster:false,
//          colorDropFuzziness:25, dropColor:'255,255,255', enableColorDrop:false }
// OMR:   { type:'omr', name:'OMR1', x, y, w, h (normalized 0-1),
//          labelText:'A B C D', footerMargin:15, headerMargin:13, leftMargin:11, rightMargin:15,
//          bubbleRectX:0, bubbleRectY:0, bubbleRectW:29, bubbleRectH:28,
//          groupName:'', column:4, dataType:'TEXTUAL', isQuestionType:true,
//          isReverseOrder:false, omrRegionType:'MULTIPLE', regionOrientation:'HORIZONTAL',
//          row:15, startIndex:1, isRegionContainNumeric:false }
// Barcode: { type:'barcode', name:'BARCODE1', x, y, w, h (normalized 0-1) }

// === TOOLBAR HANDLERS ===
function newTemplate() {
  document.getElementById('tplImageInput').click();
}

function startPlacePivot() {
  if (!editorImage) { showToast('Load an image first', 'info'); return; }
  if (pivotCount >= 4) { showToast('Maximum 4 pivots already placed', 'info'); return; }
  editorMode = 'pivot';
  updateEditorCursor();
  updateModeIndicator();
  showToast('Click on the image to place PIVOT' + (pivotCount + 1), 'info');
}

function startDrawOMR() {
  if (!editorImage) { showToast('Load an image first', 'info'); return; }
  editorMode = 'omr';
  updateEditorCursor();
  updateModeIndicator();
  showToast('Click and drag on the image to draw an OMR region', 'info');
}

function startDrawBarcode() {
  if (!editorImage) { showToast('Load an image first', 'info'); return; }
  editorMode = 'barcode';
  updateEditorCursor();
  updateModeIndicator();
  showToast('Click and drag on the image to draw a Barcode region', 'info');
}

function startSetMasterPivot() {
  if (!editorImage) { showToast('Load an image first', 'info'); return; }
  const pivots = editorRegions.filter(r => r.type === 'pivot');
  if (pivots.length === 0) { showToast('Place pivot markers first', 'info'); return; }
  editorMode = 'masterpivot';
  updateEditorCursor();
  updateModeIndicator();
  showToast('Click on a pivot marker to set it as Master Pivot', 'info');
}

function startSelectMode() {
  editorMode = 'select';
  updateEditorCursor();
  updateModeIndicator();
}

function updateEditorCursor() {
  const canvas = document.getElementById('tplCanvas');
  if (!canvas) return;
  switch (editorMode) {
    case 'pivot': canvas.style.cursor = 'crosshair'; break;
    case 'omr': case 'barcode': canvas.style.cursor = 'crosshair'; break;
    case 'masterpivot': canvas.style.cursor = 'pointer'; break;
    default: canvas.style.cursor = isMoving ? 'move' : 'default'; break;
  }
}

function updateModeIndicator() {
  const el = document.getElementById('editorModeIndicator');
  if (!el) return;
  const modes = {
    select: '<i class="fas fa-mouse-pointer"></i> Select',
    pivot: '<i class="fas fa-crosshairs"></i> Place Pivot',
    omr: '<i class="fas fa-th-large"></i> Draw OMR',
    barcode: '<i class="fas fa-barcode"></i> Draw Barcode',
    masterpivot: '<i class="fas fa-star"></i> Set Master Pivot'
  };
  el.innerHTML = modes[editorMode] || 'Select';
  el.className = 'mode-indicator mode-' + editorMode;
}

// === IMAGE LOADING ===
function handleTemplateImageLoad(event) {
  const file = event.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const img = new Image();
    img.onload = function() {
      editorImage = img;
      editorImageDataURL = e.target.result;
      editorRegions = [];
      selectedRegionIdx = -1;
      pivotCount = 0;
      omrRegionCount = 0;
      barcodeRegionCount = 0;
      editorMode = 'select';

      // Show canvas tools
      document.getElementById('canvasTools').style.display = 'flex';
      
      renderEditorCanvas();
      updatePropertiesPanel();
      showToast('Image loaded: ' + img.width + 'x' + img.height + 'px', 'success');
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
  // Reset file input so same file can be re-selected
  event.target.value = '';
}

// === CANVAS RENDERING ===
function renderEditorCanvas() {
  const body = document.getElementById('templateEditorBody');
  if (!body) return;
  
  if (!editorImage) {
    body.innerHTML = '<div class="empty-state"><i class="fas fa-image"></i><p>Click "New Page" or "Load Image" to load an OMR sheet image</p></div>';
    return;
  }
  
  // Calculate canvas size to fit the panel
  const panelRect = body.getBoundingClientRect();
  const maxW = panelRect.width - 20;
  const maxH = panelRect.height - 10;
  const imgRatio = editorImage.width / editorImage.height;
  
  let cw, ch;
  if (maxW / maxH > imgRatio) {
    ch = Math.min(maxH, editorImage.height);
    cw = ch * imgRatio;
  } else {
    cw = Math.min(maxW, editorImage.width);
    ch = cw / imgRatio;
  }
  
  editorCanvasW = Math.round(cw);
  editorCanvasH = Math.round(ch);
  
  body.innerHTML = `
    <div class="tpl-canvas-wrapper" id="tplCanvasWrapper">
      <div class="tpl-canvas-container" id="tplCanvasContainer">
        <canvas id="tplCanvas" width="${editorCanvasW}" height="${editorCanvasH}"></canvas>
      </div>
    </div>
    <div class="editor-bottom-bar">
      <div class="editor-checkboxes">
        <label><input type="checkbox" id="chkShowLabels" ${showLabels ? 'checked' : ''} onchange="showLabels=this.checked;drawEditor()"> Show Labels</label>
        <label><input type="checkbox" id="chkLowContrast" ${lowContrast ? 'checked' : ''} onchange="lowContrast=this.checked;drawEditor()"> Low Contrast</label>
        <label><input type="checkbox" id="chkNoise" ${showNoise ? 'checked' : ''} onchange="showNoise=this.checked;drawEditor()"> Noise</label>
      </div>
      <div class="editor-mode" id="editorModeIndicator">
        <i class="fas fa-mouse-pointer"></i> Select
      </div>
      <div class="editor-coords" id="editorCoords">X: 0 Y: 0</div>
    </div>
  `;
  
  const canvas = document.getElementById('tplCanvas');
  if (!canvas) return;
  
  canvas.addEventListener('mousedown', onEditorMouseDown);
  canvas.addEventListener('mousemove', onEditorMouseMove);
  canvas.addEventListener('mouseup', onEditorMouseUp);
  canvas.addEventListener('mouseleave', onEditorMouseLeave);
  canvas.addEventListener('contextmenu', function(e) { e.preventDefault(); });
  
  drawEditor();
  updateModeIndicator();
}

function drawEditor() {
  const canvas = document.getElementById('tplCanvas');
  if (!canvas || !editorImage) return;
  const ctx = canvas.getContext('2d');
  const W = editorCanvasW;
  const H = editorCanvasH;
  
  ctx.clearRect(0, 0, W, H);
  
  // Draw background image
  if (lowContrast) {
    ctx.globalAlpha = 0.4;
  }
  ctx.drawImage(editorImage, 0, 0, W, H);
  ctx.globalAlpha = 1.0;
  
  // Draw all regions
  editorRegions.forEach((r, i) => {
    const isSelected = (selectedRegionIdx === i);
    drawRegion(ctx, r, i, isSelected, W, H);
  });
  
  // Draw temporary drag rectangle
  if (isDragging && drawStart && (editorMode === 'omr' || editorMode === 'barcode')) {
    const canvas = document.getElementById('tplCanvas');
    const rect = canvas.getBoundingClientRect();
    // drawStart has the start position, current mouse position is tracked separately
    // This is handled in onEditorMouseMove
  }
}

function drawRegion(ctx, r, idx, isSelected, W, H) {
  if (r.type === 'pivot') {
    drawPivotMarker(ctx, r, idx, isSelected, W, H);
  } else if (r.type === 'omr') {
    drawOMRRegion(ctx, r, idx, isSelected, W, H);
  } else if (r.type === 'barcode') {
    drawBarcodeRegion(ctx, r, idx, isSelected, W, H);
  }
}

function drawPivotMarker(ctx, r, idx, isSelected, W, H) {
  const px = r.x * W;
  const py = r.y * H;
  const size = Math.max(14, W * 0.018);
  
  // Filled square
  ctx.fillStyle = r.isMaster ? '#FF5722' : '#FF9800';
  ctx.globalAlpha = 0.85;
  ctx.fillRect(px - size/2, py - size/2, size, size);
  ctx.globalAlpha = 1;
  
  // White cross pattern inside
  ctx.strokeStyle = '#ffffff';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.moveTo(px, py - size/2 + 3);
  ctx.lineTo(px, py + size/2 - 3);
  ctx.moveTo(px - size/2 + 3, py);
  ctx.lineTo(px + size/2 - 3, py);
  ctx.stroke();
  
  // Border
  ctx.strokeStyle = isSelected ? '#FFEB3B' : (r.isMaster ? '#BF360C' : '#E65100');
  ctx.lineWidth = isSelected ? 3 : 1.5;
  ctx.strokeRect(px - size/2, py - size/2, size, size);
  
  // Label
  if (showLabels) {
    ctx.fillStyle = r.isMaster ? '#FF5722' : '#FF9800';
    ctx.font = 'bold 11px Segoe UI';
    ctx.textAlign = 'center';
    const labelText = r.name + (r.isMaster ? ' (Master)' : '');
    // Background for label
    const metrics = ctx.measureText(labelText);
    const labelY = py + size/2 + 14;
    ctx.fillStyle = 'rgba(255,255,255,0.85)';
    ctx.fillRect(px - metrics.width/2 - 3, labelY - 10, metrics.width + 6, 14);
    ctx.fillStyle = r.isMaster ? '#FF5722' : '#FF9800';
    ctx.fillText(labelText, px, labelY);
  }
  
  // Selection glow
  if (isSelected) {
    ctx.strokeStyle = '#FFEB3B';
    ctx.lineWidth = 2;
    ctx.setLineDash([4, 2]);
    ctx.strokeRect(px - size/2 - 4, py - size/2 - 4, size + 8, size + 8);
    ctx.setLineDash([]);
  }
}

function drawOMRRegion(ctx, r, idx, isSelected, W, H) {
  const rx = r.x * W;
  const ry = r.y * H;
  const rw = r.w * W;
  const rh = r.h * H;
  
  // Fill
  ctx.fillStyle = 'rgba(255, 182, 193, 0.25)';
  ctx.fillRect(rx, ry, rw, rh);
  
  // Border
  ctx.strokeStyle = isSelected ? '#FFEB3B' : '#E91E63';
  ctx.lineWidth = isSelected ? 3 : 2;
  ctx.strokeRect(rx, ry, rw, rh);
  
  // Draw bubble grid inside region
  if (r.row > 0 && r.column > 0) {
    drawBubbleGridInRegion(ctx, rx, ry, rw, rh, r);
  }
  
  // Label
  if (showLabels) {
    ctx.fillStyle = 'rgba(233, 30, 99, 0.9)';
    ctx.font = 'bold 11px Segoe UI';
    ctx.textAlign = 'left';
    const label = r.name + ' (' + r.omrRegionType + ', ' + r.row + 'x' + r.column + ')';
    const metrics = ctx.measureText(label);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fillRect(rx, ry - 16, metrics.width + 8, 16);
    ctx.fillStyle = '#E91E63';
    ctx.fillText(label, rx + 4, ry - 4);
  }
  
  // Resize handles if selected
  if (isSelected) {
    drawResizeHandles(ctx, rx, ry, rw, rh);
  }
}

function drawBubbleGridInRegion(ctx, rx, ry, rw, rh, r) {
  const rows = r.row || 1;
  const cols = r.column || 4;
  const labels = (r.labelText || 'A B C D').split(' ');
  
  // Calculate margins (as percentage of region)
  const hMarginPx = (r.headerMargin || 0) * rh / 100;
  const fMarginPx = (r.footerMargin || 0) * rh / 100;
  const lMarginPx = (r.leftMargin || 0) * rw / 100;
  const rMarginPx = (r.rightMargin || 0) * rw / 100;
  
  const innerX = rx + lMarginPx;
  const innerY = ry + hMarginPx;
  const innerW = rw - lMarginPx - rMarginPx;
  const innerH = rh - hMarginPx - fMarginPx;
  
  if (innerW <= 0 || innerH <= 0) return;
  
  const cellW = innerW / cols;
  const cellH = innerH / rows;
  const bubbleR = Math.min(cellW, cellH) * 0.3;
  
  if (bubbleR < 2) return; // Too small to draw
  
  ctx.save();
  ctx.globalAlpha = 0.6;
  
  for (let row = 0; row < rows; row++) {
    for (let col = 0; col < cols; col++) {
      const cx = innerX + col * cellW + cellW / 2;
      const cy = innerY + row * cellH + cellH / 2;
      
      // Draw bubble circle
      ctx.beginPath();
      ctx.arc(cx, cy, bubbleR, 0, Math.PI * 2);
      ctx.strokeStyle = '#E91E63';
      ctx.lineWidth = 1;
      ctx.stroke();
      
      // Label inside bubble
      if (bubbleR > 4 && showLabels && col < labels.length) {
        ctx.fillStyle = '#E91E63';
        ctx.font = Math.max(7, bubbleR * 0.9) + 'px Segoe UI';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(labels[col], cx, cy);
      }
    }
  }
  
  ctx.restore();
  ctx.textBaseline = 'alphabetic';
}

function drawBarcodeRegion(ctx, r, idx, isSelected, W, H) {
  const rx = r.x * W;
  const ry = r.y * H;
  const rw = r.w * W;
  const rh = r.h * H;
  
  // Fill
  ctx.fillStyle = 'rgba(33, 150, 243, 0.15)';
  ctx.fillRect(rx, ry, rw, rh);
  
  // Border
  ctx.strokeStyle = isSelected ? '#FFEB3B' : '#2196F3';
  ctx.lineWidth = isSelected ? 3 : 2;
  ctx.setLineDash([6, 3]);
  ctx.strokeRect(rx, ry, rw, rh);
  ctx.setLineDash([]);
  
  // Barcode icon lines
  const barX = rx + rw/2 - 30;
  const barY = ry + rh/2 - 8;
  ctx.fillStyle = '#2196F3';
  for (let i = 0; i < 15; i++) {
    const bw = (i % 3 === 0) ? 3 : 1.5;
    ctx.fillRect(barX + i * 4, barY, bw, 16);
  }
  
  // Label
  if (showLabels) {
    ctx.fillStyle = '#2196F3';
    ctx.font = 'bold 10px Segoe UI';
    ctx.textAlign = 'left';
    const metrics = ctx.measureText(r.name);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fillRect(rx, ry - 16, metrics.width + 8, 16);
    ctx.fillStyle = '#2196F3';
    ctx.fillText(r.name, rx + 4, ry - 4);
  }
  
  // Resize handles if selected
  if (isSelected) {
    drawResizeHandles(ctx, rx, ry, rw, rh);
  }
}

function drawResizeHandles(ctx, rx, ry, rw, rh) {
  const handleSize = 6;
  const handles = [
    { x: rx, y: ry },                    // top-left
    { x: rx + rw/2, y: ry },             // top-center
    { x: rx + rw, y: ry },               // top-right
    { x: rx + rw, y: ry + rh/2 },        // middle-right
    { x: rx + rw, y: ry + rh },          // bottom-right
    { x: rx + rw/2, y: ry + rh },        // bottom-center
    { x: rx, y: ry + rh },               // bottom-left
    { x: rx, y: ry + rh/2 },             // middle-left
  ];
  
  handles.forEach(h => {
    ctx.fillStyle = '#FFEB3B';
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 1;
    ctx.fillRect(h.x - handleSize/2, h.y - handleSize/2, handleSize, handleSize);
    ctx.strokeRect(h.x - handleSize/2, h.y - handleSize/2, handleSize, handleSize);
  });
}

// === MOUSE INTERACTION ===
function getCanvasPos(e) {
  const canvas = document.getElementById('tplCanvas');
  if (!canvas) return { x: 0, y: 0 };
  const rect = canvas.getBoundingClientRect();
  const scaleX = editorCanvasW / rect.width;
  const scaleY = editorCanvasH / rect.height;
  return {
    x: (e.clientX - rect.left) * scaleX / editorCanvasW,  // normalized 0-1
    y: (e.clientY - rect.top) * scaleY / editorCanvasH     // normalized 0-1
  };
}

function onEditorMouseDown(e) {
  const pos = getCanvasPos(e);
  
  if (editorMode === 'pivot') {
    // Place a new pivot
    if (pivotCount >= 4) {
      showToast('Maximum 4 pivots', 'info');
      editorMode = 'select';
      updateModeIndicator();
      return;
    }
    pivotCount++;
    const pivot = {
      type: 'pivot',
      name: 'PIVOT' + pivotCount,
      x: pos.x,
      y: pos.y,
      isMaster: pivotCount === 1, // First pivot is master by default
      colorDropFuzziness: 25,
      dropColor: '255,255,255',
      enableColorDrop: false
    };
    editorRegions.push(pivot);
    selectedRegionIdx = editorRegions.length - 1;
    drawEditor();
    updatePropertiesPanel();
    renderRegionList();
    
    if (pivotCount < 4) {
      showToast('PIVOT' + pivotCount + ' placed. Click to place PIVOT' + (pivotCount + 1), 'success');
    } else {
      showToast('All 4 pivots placed', 'success');
      editorMode = 'select';
      updateModeIndicator();
    }
    return;
  }
  
  if (editorMode === 'masterpivot') {
    // Find nearest pivot and set as master
    let nearest = -1;
    let minDist = Infinity;
    editorRegions.forEach((r, i) => {
      if (r.type !== 'pivot') return;
      const dist = Math.sqrt((r.x - pos.x)**2 + (r.y - pos.y)**2);
      if (dist < minDist && dist < 0.05) {
        minDist = dist;
        nearest = i;
      }
    });
    if (nearest >= 0) {
      editorRegions.forEach(r => { if (r.type === 'pivot') r.isMaster = false; });
      editorRegions[nearest].isMaster = true;
      selectedRegionIdx = nearest;
      showToast(editorRegions[nearest].name + ' set as Master Pivot', 'success');
      editorMode = 'select';
      updateModeIndicator();
      drawEditor();
      updatePropertiesPanel();
    } else {
      showToast('Click on a pivot marker', 'info');
    }
    return;
  }
  
  if (editorMode === 'omr' || editorMode === 'barcode') {
    // Start drawing a rectangle
    drawStart = { x: pos.x, y: pos.y };
    isDragging = true;
    return;
  }
  
  // Select mode - find region under cursor
  if (editorMode === 'select') {
    const hitIdx = findRegionAtPos(pos);
    if (hitIdx >= 0) {
      selectedRegionIdx = hitIdx;
      const r = editorRegions[hitIdx];
      
      // Check if clicking a resize handle
      if (r.type !== 'pivot') {
        const handle = getResizeHandle(pos, r);
        if (handle) {
          isResizing = true;
          resizeHandle = handle;
          drawEditor();
          updatePropertiesPanel();
          return;
        }
      }
      
      // Start moving
      isMoving = true;
      if (r.type === 'pivot') {
        moveOffset = { x: pos.x - r.x, y: pos.y - r.y };
      } else {
        moveOffset = { x: pos.x - r.x, y: pos.y - r.y };
      }
      updateEditorCursor();
    } else {
      selectedRegionIdx = -1;
    }
    drawEditor();
    updatePropertiesPanel();
  }
}

function onEditorMouseMove(e) {
  const pos = getCanvasPos(e);
  
  // Update coordinates display
  const coordsEl = document.getElementById('editorCoords');
  if (coordsEl) {
    coordsEl.textContent = 'X: ' + Math.round(pos.x * (editorImage ? editorImage.width : 0)) + 
                           ' Y: ' + Math.round(pos.y * (editorImage ? editorImage.height : 0));
  }
  
  // Drawing rectangle
  if (isDragging && drawStart && (editorMode === 'omr' || editorMode === 'barcode')) {
    drawEditor();
    // Draw temporary rectangle
    const canvas = document.getElementById('tplCanvas');
    const ctx = canvas.getContext('2d');
    const x1 = Math.min(drawStart.x, pos.x) * editorCanvasW;
    const y1 = Math.min(drawStart.y, pos.y) * editorCanvasH;
    const w = Math.abs(pos.x - drawStart.x) * editorCanvasW;
    const h = Math.abs(pos.y - drawStart.y) * editorCanvasH;
    
    ctx.strokeStyle = editorMode === 'omr' ? '#E91E63' : '#2196F3';
    ctx.lineWidth = 2;
    ctx.setLineDash([6, 3]);
    ctx.strokeRect(x1, y1, w, h);
    ctx.setLineDash([]);
    ctx.fillStyle = editorMode === 'omr' ? 'rgba(233, 30, 99, 0.1)' : 'rgba(33, 150, 243, 0.1)';
    ctx.fillRect(x1, y1, w, h);
    return;
  }
  
  // Moving a region
  if (isMoving && selectedRegionIdx >= 0) {
    const r = editorRegions[selectedRegionIdx];
    if (r.type === 'pivot') {
      r.x = pos.x - moveOffset.x;
      r.y = pos.y - moveOffset.y;
    } else {
      r.x = pos.x - moveOffset.x;
      r.y = pos.y - moveOffset.y;
    }
    // Clamp
    r.x = Math.max(0, Math.min(1 - (r.w || 0), r.x));
    r.y = Math.max(0, Math.min(1 - (r.h || 0), r.y));
    drawEditor();
    updatePropertiesPanel();
    return;
  }
  
  // Resizing
  if (isResizing && selectedRegionIdx >= 0 && resizeHandle) {
    const r = editorRegions[selectedRegionIdx];
    resizeRegion(r, pos, resizeHandle);
    drawEditor();
    updatePropertiesPanel();
    return;
  }
}

function onEditorMouseUp(e) {
  if (isDragging && drawStart && (editorMode === 'omr' || editorMode === 'barcode')) {
    const pos = getCanvasPos(e);
    const x = Math.min(drawStart.x, pos.x);
    const y = Math.min(drawStart.y, pos.y);
    const w = Math.abs(pos.x - drawStart.x);
    const h = Math.abs(pos.y - drawStart.y);
    
    if (w > 0.01 && h > 0.01) { // Minimum size check
      if (editorMode === 'omr') {
        omrRegionCount++;
        const region = {
          type: 'omr',
          name: 'OMR' + omrRegionCount,
          x: x, y: y, w: w, h: h,
          labelText: 'A B C D',
          footerMargin: 15,
          headerMargin: 13,
          leftMargin: 11,
          rightMargin: 15,
          bubbleRectX: 0, bubbleRectY: 0,
          bubbleRectW: 29, bubbleRectH: 28,
          groupName: '',
          column: 4,
          dataType: 'TEXTUAL',
          isQuestionType: true,
          isReverseOrder: false,
          omrRegionType: 'MULTIPLE',
          regionOrientation: 'HORIZONTAL',
          row: 15,
          startIndex: 1,
          isRegionContainNumeric: false
        };
        editorRegions.push(region);
        selectedRegionIdx = editorRegions.length - 1;
        showToast('OMR region "' + region.name + '" created', 'success');
      } else {
        barcodeRegionCount++;
        const region = {
          type: 'barcode',
          name: 'BARCODE' + barcodeRegionCount,
          x: x, y: y, w: w, h: h
        };
        editorRegions.push(region);
        selectedRegionIdx = editorRegions.length - 1;
        showToast('Barcode region created', 'success');
      }
      drawEditor();
      updatePropertiesPanel();
      renderRegionList();
    }
    
    isDragging = false;
    drawStart = null;
    editorMode = 'select';
    updateModeIndicator();
    updateEditorCursor();
    return;
  }
  
  isMoving = false;
  isResizing = false;
  resizeHandle = null;
  isDragging = false;
  drawStart = null;
  updateEditorCursor();
}

function onEditorMouseLeave(e) {
  if (isDragging) {
    // Complete the action
    onEditorMouseUp(e);
  }
  isMoving = false;
  isResizing = false;
}

// === HIT TESTING ===
function findRegionAtPos(pos) {
  // Check in reverse order (top-most first)
  for (let i = editorRegions.length - 1; i >= 0; i--) {
    const r = editorRegions[i];
    if (r.type === 'pivot') {
      const dist = Math.sqrt((r.x - pos.x)**2 + (r.y - pos.y)**2);
      if (dist < 0.02) return i;
    } else {
      if (pos.x >= r.x && pos.x <= r.x + r.w && pos.y >= r.y && pos.y <= r.y + r.h) return i;
    }
  }
  return -1;
}

function getResizeHandle(pos, r) {
  const handleSize = 0.01; // normalized
  const handles = [
    { name: 'tl', x: r.x, y: r.y },
    { name: 'tc', x: r.x + r.w/2, y: r.y },
    { name: 'tr', x: r.x + r.w, y: r.y },
    { name: 'mr', x: r.x + r.w, y: r.y + r.h/2 },
    { name: 'br', x: r.x + r.w, y: r.y + r.h },
    { name: 'bc', x: r.x + r.w/2, y: r.y + r.h },
    { name: 'bl', x: r.x, y: r.y + r.h },
    { name: 'ml', x: r.x, y: r.y + r.h/2 },
  ];
  for (const h of handles) {
    if (Math.abs(pos.x - h.x) < handleSize && Math.abs(pos.y - h.y) < handleSize) {
      return h.name;
    }
  }
  return null;
}

function resizeRegion(r, pos, handle) {
  switch (handle) {
    case 'tl':
      r.w += r.x - pos.x;
      r.h += r.y - pos.y;
      r.x = pos.x;
      r.y = pos.y;
      break;
    case 'tc':
      r.h += r.y - pos.y;
      r.y = pos.y;
      break;
    case 'tr':
      r.w = pos.x - r.x;
      r.h += r.y - pos.y;
      r.y = pos.y;
      break;
    case 'mr':
      r.w = pos.x - r.x;
      break;
    case 'br':
      r.w = pos.x - r.x;
      r.h = pos.y - r.y;
      break;
    case 'bc':
      r.h = pos.y - r.y;
      break;
    case 'bl':
      r.w += r.x - pos.x;
      r.x = pos.x;
      r.h = pos.y - r.y;
      break;
    case 'ml':
      r.w += r.x - pos.x;
      r.x = pos.x;
      break;
  }
  // Minimum size
  r.w = Math.max(0.02, r.w);
  r.h = Math.max(0.02, r.h);
}

// === PROPERTIES PANEL ===
function updatePropertiesPanel() {
  const body = document.getElementById('propertiesBody');
  if (!body) return;
  
  if (selectedRegionIdx < 0 || !editorRegions[selectedRegionIdx]) {
    // Show template info
    body.innerHTML = `
      <div class="prop-section">
        <div class="prop-section-title">Template Info</div>
        <div class="prop-row"><label>Image:</label><span>${editorImage ? editorImage.width + 'x' + editorImage.height : 'None'}</span></div>
        <div class="prop-row"><label>Regions:</label><span>${editorRegions.length}</span></div>
        <div class="prop-row"><label>Pivots:</label><span>${editorRegions.filter(r=>r.type==='pivot').length}/4</span></div>
        <div class="prop-row"><label>OMR:</label><span>${editorRegions.filter(r=>r.type==='omr').length}</span></div>
        <div class="prop-row"><label>Barcode:</label><span>${editorRegions.filter(r=>r.type==='barcode').length}</span></div>
      </div>
      <div class="prop-section">
        <div class="prop-section-title">Instructions</div>
        <div class="prop-hint">1. Load an OMR sheet image</div>
        <div class="prop-hint">2. Click Pivot to place corner markers</div>
        <div class="prop-hint">3. Click OMR to draw answer regions</div>
        <div class="prop-hint">4. Click BarCode to draw barcode region</div>
        <div class="prop-hint">5. Set Master Pivot for alignment</div>
        <div class="prop-hint">6. Configure properties for each region</div>
      </div>
    `;
    return;
  }
  
  const r = editorRegions[selectedRegionIdx];
  
  if (r.type === 'pivot') {
    body.innerHTML = buildPivotProperties(r, selectedRegionIdx);
  } else if (r.type === 'omr') {
    body.innerHTML = buildOMRProperties(r, selectedRegionIdx);
  } else if (r.type === 'barcode') {
    body.innerHTML = buildBarcodeProperties(r, selectedRegionIdx);
  }
}

function buildPivotProperties(r, idx) {
  return `
    <div class="prop-section">
      <div class="prop-section-title"><i class="fas fa-crosshairs"></i> Color Drop</div>
      <div class="prop-row"><label>ColorDropFuzziness:</label>
        <input type="number" class="prop-input" value="${r.colorDropFuzziness}" onchange="updateRegionProp(${idx},'colorDropFuzziness',parseInt(this.value))"></div>
      <div class="prop-row"><label>DropColor:</label>
        <input type="text" class="prop-input" value="${r.dropColor}" onchange="updateRegionProp(${idx},'dropColor',this.value)" style="width:90px"></div>
      <div class="prop-row"><label>EnableColorDrop:</label>
        <select class="prop-input" style="width:70px" onchange="updateRegionProp(${idx},'enableColorDrop',this.value==='true')">
          <option value="false" ${!r.enableColorDrop?'selected':''}>False</option>
          <option value="true" ${r.enableColorDrop?'selected':''}>True</option>
        </select></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">Property</div>
      <div class="prop-row"><label>RectangleCount:</label><span>0</span></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">User Input</div>
      <div class="prop-row"><label>RegionName:</label>
        <input type="text" class="prop-input" value="${r.name}" onchange="updateRegionProp(${idx},'name',this.value);drawEditor()" style="width:90px"></div>
      <div class="prop-row"><label>X (px):</label><span>${Math.round(r.x * (editorImage?editorImage.width:0))}</span></div>
      <div class="prop-row"><label>Y (px):</label><span>${Math.round(r.y * (editorImage?editorImage.height:0))}</span></div>
      <div class="prop-row"><label>Is Master:</label>
        <select class="prop-input" style="width:70px" onchange="setMasterPivot(${idx},this.value==='true')">
          <option value="false" ${!r.isMaster?'selected':''}>False</option>
          <option value="true" ${r.isMaster?'selected':''}>True</option>
        </select></div>
    </div>
    <div class="prop-actions">
      <button class="btn-sm btn-danger-outline" onclick="deleteRegion(${idx})"><i class="fas fa-trash"></i> Delete</button>
    </div>
  `;
}

function buildOMRProperties(r, idx) {
  return `
    <div class="prop-section">
      <div class="prop-section-title"><i class="fas fa-th-large"></i> Bubble Labels</div>
      <div class="prop-row"><label>LabelText:</label>
        <input type="text" class="prop-input" value="${r.labelText}" onchange="updateRegionProp(${idx},'labelText',this.value);drawEditor()" style="width:110px"></div>
      <div class="prop-row"><label>DefaultValue:</label>
        <input type="text" class="prop-input" value="" style="width:110px"></div>
      <div class="prop-row"><label>RegexString:</label>
        <input type="text" class="prop-input" value="" style="width:110px"></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">BubbleMargin</div>
      <div class="prop-row"><label>FooterMargin:</label>
        <input type="number" class="prop-input" value="${r.footerMargin}" onchange="updateRegionProp(${idx},'footerMargin',parseInt(this.value));drawEditor()"></div>
      <div class="prop-row"><label>HeaderMargin:</label>
        <input type="number" class="prop-input" value="${r.headerMargin}" onchange="updateRegionProp(${idx},'headerMargin',parseInt(this.value));drawEditor()"></div>
      <div class="prop-row"><label>LeftMargin:</label>
        <input type="number" class="prop-input" value="${r.leftMargin}" onchange="updateRegionProp(${idx},'leftMargin',parseInt(this.value));drawEditor()"></div>
      <div class="prop-row"><label>RightMargin:</label>
        <input type="number" class="prop-input" value="${r.rightMargin}" onchange="updateRegionProp(${idx},'rightMargin',parseInt(this.value));drawEditor()"></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">Misc</div>
      <div class="prop-row"><label>IsRegionContainNumeric:</label>
        <select class="prop-input" style="width:70px" onchange="updateRegionProp(${idx},'isRegionContainNumeric',this.value==='true')">
          <option value="false" ${!r.isRegionContainNumeric?'selected':''}>False</option>
          <option value="true" ${r.isRegionContainNumeric?'selected':''}>True</option>
        </select></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">Property</div>
      <div class="prop-row"><label>BubbleRectangle:</label>
        <input type="text" class="prop-input" value="${r.bubbleRectX}, ${r.bubbleRectY}, ${r.bubbleRectW}, ${r.bubbleRectH}" 
          onchange="parseBubbleRect(${idx},this.value)" style="width:110px"></div>
      <div class="prop-row"><label>GroupName:</label>
        <input type="text" class="prop-input" value="${r.groupName}" onchange="updateRegionProp(${idx},'groupName',this.value)" style="width:110px"></div>
    </div>
    <div class="prop-section">
      <div class="prop-section-title">User Input</div>
      <div class="prop-row"><label>Column:</label>
        <input type="number" class="prop-input" value="${r.column}" onchange="updateRegionProp(${idx},'column',parseInt(this.value));drawEditor()"></div>
      <div class="prop-row"><label>DataType:</label>
        <select class="prop-input" style="width:90px" onchange="updateRegionProp(${idx},'dataType',this.value)">
          <option value="TEXTUAL" ${r.dataType==='TEXTUAL'?'selected':''}>TEXTUAL</option>
          <option value="NUMERIC" ${r.dataType==='NUMERIC'?'selected':''}>NUMERIC</option>
        </select></div>
      <div class="prop-row"><label>IsQuestiontype:</label>
        <select class="prop-input" style="width:70px" onchange="updateRegionProp(${idx},'isQuestionType',this.value==='true')">
          <option value="false" ${!r.isQuestionType?'selected':''}>False</option>
          <option value="true" ${r.isQuestionType?'selected':''}>True</option>
        </select></div>
      <div class="prop-row"><label>IsReverseOrder:</label>
        <select class="prop-input" style="width:70px" onchange="updateRegionProp(${idx},'isReverseOrder',this.value==='true')">
          <option value="false" ${!r.isReverseOrder?'selected':''}>False</option>
          <option value="true" ${r.isReverseOrder?'selected':''}>True</option>
        </select></div>
      <div class="prop-row"><label>OMRRegionType:</label>
        <select class="prop-input prop-highlight" style="width:90px" onchange="updateRegionProp(${idx},'omrRegionType',this.value);drawEditor()">
          <option value="MULTIPLE" ${r.omrRegionType==='MULTIPLE'?'selected':''}>MULTIPLE</option>
          <option value="LIST" ${r.omrRegionType==='LIST'?'selected':''}>LIST</option>
        </select></div>
      <div class="prop-row"><label>RegionName:</label>
        <input type="text" class="prop-input" value="${r.name}" onchange="updateRegionProp(${idx},'name',this.value);drawEditor()" style="width:110px"></div>
      <div class="prop-row"><label>RegionOrientation:</label>
        <select class="prop-input" style="width:110px" onchange="updateRegionProp(${idx},'regionOrientation',this.value);drawEditor()">
          <option value="HORIZONTAL" ${r.regionOrientation==='HORIZONTAL'?'selected':''}>HORIZONTAL</option>
          <option value="VERTICAL" ${r.regionOrientation==='VERTICAL'?'selected':''}>VERTICAL</option>
        </select></div>
      <div class="prop-row"><label>Row:</label>
        <input type="number" class="prop-input" value="${r.row}" onchange="updateRegionProp(${idx},'row',parseInt(this.value));drawEditor()"></div>
      <div class="prop-row"><label>StartIndex:</label>
        <input type="number" class="prop-input" value="${r.startIndex}" onchange="updateRegionProp(${idx},'startIndex',parseInt(this.value))"></div>
    </div>
    <div class="prop-section prop-description">
      <strong>${getSelectedPropName()}</strong><br>
      <span>${getSelectedPropDescription()}</span>
    </div>
    <div class="prop-actions">
      <button class="btn-sm btn-danger-outline" onclick="deleteRegion(${idx})"><i class="fas fa-trash"></i> Delete</button>
    </div>
  `;
}

function buildBarcodeProperties(r, idx) {
  return `
    <div class="prop-section">
      <div class="prop-section-title"><i class="fas fa-barcode"></i> Barcode Region</div>
      <div class="prop-row"><label>RegionName:</label>
        <input type="text" class="prop-input" value="${r.name}" onchange="updateRegionProp(${idx},'name',this.value);drawEditor()" style="width:110px"></div>
      <div class="prop-row"><label>X:</label><span>${(r.x * 100).toFixed(1)}%</span></div>
      <div class="prop-row"><label>Y:</label><span>${(r.y * 100).toFixed(1)}%</span></div>
      <div class="prop-row"><label>Width:</label><span>${(r.w * 100).toFixed(1)}%</span></div>
      <div class="prop-row"><label>Height:</label><span>${(r.h * 100).toFixed(1)}%</span></div>
    </div>
    <div class="prop-actions">
      <button class="btn-sm btn-danger-outline" onclick="deleteRegion(${idx})"><i class="fas fa-trash"></i> Delete</button>
    </div>
  `;
}

function getSelectedPropName() {
  // Could track which property is focused - simplified version
  return 'OMRRegionType';
}

function getSelectedPropDescription() {
  return 'The output for this region will be in this format.';
}

// === PROPERTY UPDATE HANDLERS ===
function updateRegionProp(idx, prop, value) {
  if (editorRegions[idx]) {
    editorRegions[idx][prop] = value;
  }
}

function parseBubbleRect(idx, value) {
  const parts = value.split(',').map(s => parseInt(s.trim()));
  if (parts.length === 4 && !parts.some(isNaN)) {
    editorRegions[idx].bubbleRectX = parts[0];
    editorRegions[idx].bubbleRectY = parts[1];
    editorRegions[idx].bubbleRectW = parts[2];
    editorRegions[idx].bubbleRectH = parts[3];
    drawEditor();
  }
}

function setMasterPivot(idx, isMaster) {
  if (isMaster) {
    editorRegions.forEach(r => { if (r.type === 'pivot') r.isMaster = false; });
  }
  editorRegions[idx].isMaster = isMaster;
  drawEditor();
  updatePropertiesPanel();
}

function deleteRegion(idx) {
  if (!confirm('Delete region "' + editorRegions[idx].name + '"?')) return;
  const r = editorRegions[idx];
  if (r.type === 'pivot') pivotCount = Math.max(0, pivotCount - 1);
  editorRegions.splice(idx, 1);
  selectedRegionIdx = -1;
  drawEditor();
  updatePropertiesPanel();
  showToast('Region deleted', 'info');
}

// === CANVAS ZOOM ===
function canvasZoom(factor) {
  const container = document.getElementById('tplCanvasContainer');
  if (!container) return;
  
  if (factor === 1) {
    canvasScale = 1.0;
  } else {
    canvasScale *= factor;
  }
  canvasScale = Math.max(0.3, Math.min(3.0, canvasScale));
  container.style.transform = 'scale(' + canvasScale + ')';
  container.style.transformOrigin = 'top left';
}

function toggleCanvasLayer(layer) {
  // Legacy compatibility
  drawEditor();
}

// === MARKING SCHEME DIALOG ===
function openMarkingScheme() {
  if (editorRegions.filter(r => r.type === 'omr' && r.isQuestionType).length === 0) {
    showToast('No question-type OMR regions defined', 'info');
    return;
  }
  openModal('modalMarkingScheme');
  renderMarkingSchemeTable();
}

function renderMarkingSchemeTable() {
  const body = document.getElementById('markingSchemeBody');
  if (!body) return;
  
  const omrRegions = editorRegions.filter(r => r.type === 'omr' && r.isQuestionType);
  let html = '';
  
  omrRegions.forEach(r => {
    const totalQ = r.row || 1;
    const start = r.startIndex || 1;
    const regionName = r.name;
    
    // Region header row
    html += `<tr class="ms-region-row">
      <td style="font-weight:700;background:#e8f5e9">${regionName}</td>
      <td>${start}-${start + totalQ - 1}</td>
      <td><input type="checkbox" checked></td>
      <td><select class="prop-input"><option>SingleCorrect</option><option>MultiCorrect</option></select></td>
      <td><input type="checkbox"></td>
      <td><select class="prop-input"><option>None</option></select></td>
      <td><input type="number" class="prop-input" value="2" style="width:50px"></td>
      <td><input type="number" class="prop-input" value="0" style="width:50px"></td>
      <td><input type="number" class="prop-input" value="0" style="width:50px"></td>
      <td><select class="prop-input"><option>NONE</option></select></td>
      <td><select class="prop-input"><option>NONE</option></select></td>
      <td></td>
    </tr>`;
    
    // Individual question rows
    for (let q = 0; q < totalQ; q++) {
      const qNum = start + q;
      html += `<tr>
        <td></td>
        <td>${qNum}</td>
        <td><input type="checkbox" checked></td>
        <td><select class="prop-input"><option>SingleCorrect</option><option>MultiCorrect</option></select></td>
        <td><input type="checkbox"></td>
        <td><select class="prop-input"><option>None</option></select></td>
        <td><input type="number" class="prop-input" value="2" style="width:50px"></td>
        <td><input type="number" class="prop-input" value="0" style="width:50px"></td>
        <td><input type="number" class="prop-input" value="0" style="width:50px"></td>
        <td><select class="prop-input"><option>NONE</option></select></td>
        <td><select class="prop-input"><option>NONE</option></select></td>
        <td></td>
      </tr>`;
    }
  });
  
  body.innerHTML = html;
}

// === SAVE TEMPLATE ===
async function saveEditorTemplate() {
  if (!editorImage) { showToast('Load an image first', 'error'); return; }
  
  const name = prompt('Enter template name:', 'My Template');
  if (!name) return;
  
  // Collect all OMR question regions to determine total questions
  const omrQuestionRegions = editorRegions.filter(r => r.type === 'omr' && r.isQuestionType);
  let totalQuestions = 0;
  let optionsPerQuestion = 4;
  omrQuestionRegions.forEach(r => {
    totalQuestions += r.row;
    optionsPerQuestion = r.column;
  });
  if (totalQuestions === 0) totalQuestions = 75;
  
  // Get pivot data
  const pivots = editorRegions.filter(r => r.type === 'pivot');
  const masterPivot = pivots.find(p => p.isMaster);
  
  // Get barcode region
  const barcodeRegions = editorRegions.filter(r => r.type === 'barcode');
  
  // Build template data
  const templateData = {
    name: name,
    total_questions: totalQuestions,
    options_per_question: optionsPerQuestion,
    columns: omrQuestionRegions.length || 3,
    questions_per_column: omrQuestionRegions.length > 0 ? omrQuestionRegions[0].row : 25,
    fill_threshold: 0.30,
    correct_marks: 2,
    wrong_marks: 0,
    unanswered_marks: 0,
    has_barcode: barcodeRegions.length > 0,
    use_pivots: pivots.length > 0,
    regions: editorRegions.map(r => ({...r})),
    image_width: editorImage.width,
    image_height: editorImage.height
  };
  
  // Also set the answer region coordinates from the combined OMR regions
  if (omrQuestionRegions.length > 0) {
    const allX = omrQuestionRegions.map(r => r.x);
    const allY = omrQuestionRegions.map(r => r.y);
    const allXEnd = omrQuestionRegions.map(r => r.x + r.w);
    const allYEnd = omrQuestionRegions.map(r => r.y + r.h);
    templateData.answer_region_x_start = Math.min(...allX);
    templateData.answer_region_y_start = Math.min(...allY);
    templateData.answer_region_x_end = Math.max(...allXEnd);
    templateData.answer_region_y_end = Math.max(...allYEnd);
  }
  
  if (barcodeRegions.length > 0) {
    templateData.barcode_region_x = barcodeRegions[0].x;
    templateData.barcode_region_y = barcodeRegions[0].y;
    templateData.barcode_region_w = barcodeRegions[0].w;
    templateData.barcode_region_h = barcodeRegions[0].h;
  }
  
  try {
    // Save template via API
    const res = await apiFetch(`${API_URL}/api/templates`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(templateData)
    });
    if (!res.ok) throw new Error(await res.text());
    
    // Also upload the template image
    if (editorImageDataURL) {
      const tplData = await res.json();
      const formData = new FormData();
      // Convert data URL to blob
      const byteString = atob(editorImageDataURL.split(',')[1]);
      const mimeString = editorImageDataURL.split(',')[0].split(':')[1].split(';')[0];
      const ab = new ArrayBuffer(byteString.length);
      const ia = new Uint8Array(ab);
      for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
      const blob = new Blob([ab], { type: mimeString });
      formData.append('image', blob, 'template.jpg');
      formData.append('template_id', tplData.id || '');
      formData.append('regions', JSON.stringify(editorRegions));
      
      await apiFetch(`${API_URL}/api/template-image`, {
        method: 'POST',
        body: formData
      });
    }
    
    showToast('Template "' + name + '" saved successfully', 'success');
    loadTemplates();
  } catch (e) {
    showToast('Failed to save template: ' + e.message, 'error');
  }
}

// === REGION LIST ===
function renderRegionList() {
  const list = document.getElementById('regionList');
  if (!list) return;
  
  if (editorRegions.length === 0) {
    list.innerHTML = '<div class="empty-state" style="padding:20px"><i class="fas fa-layer-group" style="font-size:24px"></i><p style="font-size:11px">No regions yet</p></div>';
    return;
  }
  
  list.innerHTML = editorRegions.map((r, i) => {
    const icon = r.type === 'pivot' ? 'fa-crosshairs' : r.type === 'omr' ? 'fa-th-large' : 'fa-barcode';
    const color = r.type === 'pivot' ? (r.isMaster ? '#FF5722' : '#FF9800') : r.type === 'omr' ? '#E91E63' : '#2196F3';
    const selected = selectedRegionIdx === i ? 'active' : '';
    return `<div class="region-item ${selected}" onclick="selectRegionByIndex(${i})" style="border-left:3px solid ${color}">
      <i class="fas ${icon}" style="color:${color}"></i>
      <span class="region-name">${r.name}</span>
      <span class="region-type">${r.type.toUpperCase()}</span>
    </div>`;
  }).join('');
}

function selectRegionByIndex(idx) {
  selectedRegionIdx = idx;
  drawEditor();
  updatePropertiesPanel();
  renderRegionList();
}

// === TEMPLATE SELECTION (connects to existing template list) ===
function selectTemplate(id) {
  const t = templates.find(x => x.id === id);
  if (!t) return;
  activeTemplate = t;
  
  document.querySelectorAll('.template-item').forEach(el => el.classList.remove('active'));
  document.getElementById('tpl-' + id)?.classList.add('active');
  
  // Show canvas tools
  document.getElementById('canvasTools').style.display = 'flex';
  
  // If template has regions, load them into the editor
  if (t.regions && t.regions.length > 0) {
    editorRegions = t.regions.map(r => ({...r}));
    selectedRegionIdx = -1;
    pivotCount = editorRegions.filter(r => r.type === 'pivot').length;
    omrRegionCount = editorRegions.filter(r => r.type === 'omr').length;
    barcodeRegionCount = editorRegions.filter(r => r.type === 'barcode').length;
    
    // If template has image, try to load it
    if (t.image_url) {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function() {
        editorImage = img;
        renderEditorCanvas();
        renderRegionList();
        updatePropertiesPanel();
      };
      img.src = t.image_url;
    } else {
      renderEditorCanvas();
      renderRegionList();
      updatePropertiesPanel();
    }
  } else {
    // Show old-style template preview
    renderOldTemplateCanvas(t);
  }
}

function renderOldTemplateCanvas(t) {
  // Fallback: render the old template visualization without an image
  const body = document.getElementById('templateEditorBody');
  const W = 550, H = 770;
  
  body.innerHTML = `
    <div class="tpl-canvas-wrapper" id="tplCanvasWrapper">
      <div class="tpl-canvas-container" id="tplCanvasContainer">
        <canvas id="tplCanvas" width="${W}" height="${H}"></canvas>
      </div>
    </div>
    <div class="editor-bottom-bar">
      <div class="editor-hint">Load an image to edit this template visually</div>
    </div>
  `;
  
  editorCanvasW = W;
  editorCanvasH = H;
  
  // Draw old-style template
  drawOldTemplate(t);
  
  // Update properties panel with old template info
  const propsBody = document.getElementById('propertiesBody');
  if (propsBody) {
    propsBody.innerHTML = `
      <div class="prop-section">
        <div class="prop-section-title">Template Info</div>
        <div class="prop-row"><label>Name:</label><span>${t.name}</span></div>
        <div class="prop-row"><label>Questions:</label><span>${t.total_questions}</span></div>
        <div class="prop-row"><label>Options:</label><span>${t.options_per_question}</span></div>
        <div class="prop-row"><label>Columns:</label><span>${t.columns}</span></div>
      </div>
      <div class="prop-section">
        <div class="prop-section-title">Detection</div>
        <div class="prop-row"><label>Fill Threshold:</label><span>${t.fill_threshold}</span></div>
        <div class="prop-row"><label>Use Pivots:</label><span>${t.use_pivots ? 'Yes' : 'No'}</span></div>
        <div class="prop-row"><label>Has Barcode:</label><span>${t.has_barcode ? 'Yes' : 'No'}</span></div>
      </div>
      <div class="prop-section">
        <div class="prop-section-title">Marking</div>
        <div class="prop-row"><label>Correct:</label><span>+${t.correct_marks}</span></div>
        <div class="prop-row"><label>Wrong:</label><span>${t.wrong_marks}</span></div>
        <div class="prop-row"><label>Unanswered:</label><span>${t.unanswered_marks}</span></div>
      </div>
    `;
  }
}

function drawOldTemplate(t) {
  const canvas = document.getElementById('tplCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width;
  const H = canvas.height;
  
  ctx.clearRect(0, 0, W, H);
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, W, H);
  ctx.strokeStyle = '#333';
  ctx.lineWidth = 2;
  ctx.strokeRect(1, 1, W - 2, H - 2);
  
  // Header
  ctx.fillStyle = '#1a3a5c';
  ctx.fillRect(10, 10, W - 20, 45);
  ctx.fillStyle = '#fff';
  ctx.font = 'bold 14px Segoe UI';
  ctx.textAlign = 'center';
  ctx.fillText(t.name || 'OMR Sheet Template', W / 2, 38);
  ctx.font = '9px Segoe UI';
  ctx.fillText(t.total_questions + ' Questions | ' + t.options_per_question + ' Options | ' + t.columns + ' Columns', W / 2, 50);
  
  // OMR Region
  const omrX1 = (t.answer_region_x_start || 0.03) * W;
  const omrY1 = (t.answer_region_y_start || 0.30) * H;
  const omrX2 = (t.answer_region_x_end || 0.93) * W;
  const omrY2 = (t.answer_region_y_end || 0.93) * H;
  const omrW = omrX2 - omrX1;
  const omrH = omrY2 - omrY1;
  
  ctx.fillStyle = 'rgba(76, 175, 80, 0.05)';
  ctx.fillRect(omrX1, omrY1, omrW, omrH);
  ctx.strokeStyle = '#4CAF50';
  ctx.lineWidth = 2;
  ctx.strokeRect(omrX1, omrY1, omrW, omrH);
  
  // Draw bubbles
  const cols = t.columns || 3;
  const qPerCol = t.questions_per_column || Math.ceil(t.total_questions / cols);
  const opts = t.options_per_question || 4;
  const colW = omrW / cols;
  const optLetters = 'ABCDEFGHIJ';
  
  for (let c = 0; c < cols; c++) {
    const colX = omrX1 + c * colW + 4;
    const colRight = omrX1 + (c + 1) * colW - 4;
    const qStart = c * qPerCol + 1;
    const qEnd = Math.min(qStart + qPerCol - 1, t.total_questions);
    const numQ = qEnd - qStart + 1;
    const rowH = omrH / qPerCol;
    
    // Column header
    ctx.fillStyle = '#1a3a5c';
    ctx.fillRect(colX, omrY1, colRight - colX, 14);
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 8px Segoe UI';
    ctx.textAlign = 'center';
    ctx.fillText('Q.', colX + 18, omrY1 + 10);
    for (let o = 0; o < opts; o++) {
      const optX = colX + 38 + o * ((colRight - colX - 40) / opts) + ((colRight - colX - 40) / opts) / 2;
      ctx.fillText(optLetters[o], optX, omrY1 + 10);
    }
    
    for (let q = 0; q < numQ; q++) {
      const qNum = qStart + q;
      const rowY = omrY1 + 16 + q * (rowH - 16 / qPerCol);
      
      ctx.fillStyle = '#555';
      ctx.font = '8px Segoe UI';
      ctx.textAlign = 'right';
      ctx.fillText(qNum + '.', colX + 20, rowY + (rowH - 16 / qPerCol) / 2 + 3);
      
      for (let o = 0; o < opts; o++) {
        const bx = colX + 38 + o * ((colRight - colX - 40) / opts) + ((colRight - colX - 40) / opts) / 2;
        const by = rowY + (rowH - 16 / qPerCol) / 2;
        const br = Math.min(5, (rowH - 16 / qPerCol) / 3, ((colRight - colX - 40) / opts) / 3);
        
        ctx.beginPath();
        ctx.arc(bx, by, br, 0, Math.PI * 2);
        ctx.strokeStyle = '#1a3a5c';
        ctx.lineWidth = 1;
        ctx.stroke();
        ctx.fillStyle = '#1a3a5c';
        ctx.font = (br * 1.2) + 'px Segoe UI';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(optLetters[o], bx, by);
      }
      ctx.textBaseline = 'alphabetic';
    }
  }
  
  // Pivot markers
  if (t.use_pivots) {
    const pivotSize = 18;
    const pivotPositions = [
      { x: 8, y: 8, label: 'TL', color: '#FF5722', master: true },
      { x: W - 8 - pivotSize, y: 8, label: 'TR', color: '#FF9800' },
      { x: 8, y: H - 8 - pivotSize, label: 'BL', color: '#2196F3' },
      { x: W - 8 - pivotSize, y: H - 8 - pivotSize, label: 'BR', color: '#4CAF50' },
    ];
    pivotPositions.forEach(p => {
      ctx.fillStyle = p.color;
      ctx.fillRect(p.x, p.y, pivotSize, pivotSize);
      ctx.strokeStyle = '#fff';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(p.x + pivotSize/2, p.y + 3);
      ctx.lineTo(p.x + pivotSize/2, p.y + pivotSize - 3);
      ctx.moveTo(p.x + 3, p.y + pivotSize/2);
      ctx.lineTo(p.x + pivotSize - 3, p.y + pivotSize/2);
      ctx.stroke();
    });
  }
}

// === UTILITY ===
function updateRegionFromProps() {
  // Legacy compatibility - no-op for new editor
}
