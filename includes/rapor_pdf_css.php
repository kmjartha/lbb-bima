<?php
/**
 * Returns a Dompdf-safe CSS stylesheet for the rapor PDF.
 *
 * Dompdf is a CSS 2.1 renderer with partial CSS3 support — it has NO
 * flexbox and NO CSS grid. The live screen stylesheet (design-system.css)
 * uses both (.rapor-head is flex, .sig-grid is grid), so for the PDF we
 * re-implement the same visual layout using floats/tables instead, kept
 * in its own small stylesheet rather than trying to make one file serve
 * both renderers.
 *
 * Class names match assets/css/design-system.css 1:1 so rapor_render_body()
 * in includes/rapor_render.php needs no special-casing for PDF vs screen.
 */
declare(strict_types=1);

function rapor_pdf_css(): string
{
    return <<<CSS
@page { margin: 15mm; }

body {
  font-family: "DejaVu Sans", sans-serif;
  font-size: 11.5px;
  line-height: 1.5;
  color: #1f2937;
  margin: 0;
}

.rapor-page { width: 100%; }
.rapor-body { }

.rapor-banner { width: 100%; display: block; margin: 0 0 10px; }

/* Fallback header — no float/flex available, use a simple two-cell table. */
.rapor-head { width: 100%; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px; }
.rapor-head .logo { width: 56px; height: 56px; }
.rapor-head .school h2 { margin: 0 0 3px; font-size: 16px; font-weight: bold; color: #0f172a; }
.rapor-head .school .meta { font-size: 10px; color: #64748b; }
.rapor-head-right { font-size: 10.5px; color: #475569; text-align: right; }
.rapor-head-right strong { color: #0f172a; }

.rapor-subhead { text-align: right; margin: 8px 0 14px; font-size: 10.5px; color: #475569; }
.rapor-subhead strong { color: #0f172a; }

.rapor-section { margin: 11px 0; }
.rapor-section h3 {
  margin: 0 0 8px;
  font-size: 10px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #475569;
  padding-left: 8px;
  border-left: 3px solid #2563eb;
}

table.t-print { width: 100%; border-collapse: collapse; font-size: 10.5px; color: #1f2937; }
table.t-print th, table.t-print td {
  border: 1px solid #e5e7eb;
  padding: 5px 8px;
  text-align: left;
  vertical-align: top;
}
table.t-print th { background: #f8fafc; font-weight: bold; color: #334155; }

.character-eval-table th.category-heading { width: 16%; }
.character-eval-table th.aspect-heading { width: 48%; }
.character-eval-table th.scale-heading { width: 9%; text-align: center; font-size: 9px; }
.character-eval-table td.category-cell { background: #eef6ff; font-weight: bold; }
.character-eval-table td.scale-cell { text-align: center; font-size: 12px; }

.rapor-empty-note { font-size: 10.5px; color: #6b7280; }
.rapor-cat-row { background: #eef2ff; font-weight: bold; }
.rapor-total-row { background: #fffbeb; font-weight: bold; }
.rapor-foot-note { margin-top: 6px; font-size: 9.5px; color: #475569; }
.rapor-kkm-legend { margin-top: 6px; font-size: 9.5px; }
.kkm-pill {
  display: inline-block;
  padding: 1px 6px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 9px;
  margin-right: 3px;
  background: #f8fafc;
  color: #334155;
}
.rapor-table-narrow { width: 70%; }

.rapor-note-box {
  border: 1px solid #cbd5e1;
  padding: 7px 10px;
  min-height: 38px;
  font-size: 10.5px;
  color: #1f2937;
  background: #f9fafb;
}

/* Signature row — Dompdf has no CSS grid, use a real 4-column table. */
.rapor-issued { text-align: right; font-size: 10.5px; margin-bottom: 6px; color: #334155; }
table.sig-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.sig-table td {
  width: 25%;
  text-align: center;
  vertical-align: top;
  padding: 4px 6px 0;
  border: none;
}
.sig-cell .role { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 3px; }
.sig-cell .ttd { height: 40px; text-align: center; }
.sig-cell .ttd img { max-height: 40px; max-width: 100%; }
.sig-cell .nama { font-weight: bold; color: #0f172a; border-top: 1px solid #cbd5e1; padding-top: 4px; font-size: 10.5px; margin-top: 18px; }
.sig-cell .jabatan { font-size: 9px; color: #64748b; }
.rapor-footer-img { text-align: center; margin-top: 10px; }
.rapor-footer-img img { max-height: 50px; }
CSS;
}
