<?php /** Shared styles for the printable Arabic request sheets. */ ?>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Cairo', 'Tajawal', 'Segoe UI', Tahoma, Arial, sans-serif;
        color: #111; background: #e9edf2; margin: 0; line-height: 1.4;
    }
    .toolbar {
        position: sticky; top: 0; z-index: 5; display: flex; gap: 10px; justify-content: center;
        padding: 12px; background: #fff; border-bottom: 1px solid #ddd;
    }
    .toolbar button, .toolbar a {
        font-family: inherit; font-size: 15px; font-weight: 600; cursor: pointer;
        border: 1px solid #cbd2da; background: #fff; color: #111;
        padding: 8px 18px; border-radius: 8px; text-decoration: none;
    }
    .toolbar button.primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .toolbar .count { align-self: center; color: #666; font-size: 14px; }

    .sheet {
        width: 210mm; min-height: 297mm; margin: 18px auto; background: #fff;
        padding: 14mm 18mm; box-shadow: 0 2px 12px rgba(0,0,0,.15);
    }

    /* ---- Letterhead ----
       The two side logos sit at the top; the eagle is slightly lower. */
    .letterhead {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; direction: ltr; padding-bottom: 10px; border-bottom: 2px solid #1f4e79;
    }
    .letterhead img { width: auto; object-fit: contain; }
    .letterhead .side  { height: 58px; margin-top: 0; }
    .letterhead .eagle { height: 66px; margin-top: 16px; }

    .meta { margin-top: 16px; font-size: 15px; }
    .meta .row { display: flex; gap: 8px; margin-bottom: 4px; }
    .meta .row .lbl { font-weight: 700; min-width: 210px; }
    .meta .blank { display: inline-block; min-width: 120px; border-bottom: 1px dotted #333; }

    h1.title {
        text-align: center; font-size: 20px; font-weight: 700; color: #1f4e79;
        margin: 20px 0 16px; padding: 8px 0; border-top: 1px solid #d0d0d0; border-bottom: 1px solid #d0d0d0;
    }

    .lead { font-size: 15px; margin: 0 0 14px; }

    table.details { width: 100%; border-collapse: collapse; font-size: 14.5px; margin-top: 8px; }
    table.details th, table.details td { border: 1px solid #c9c9c9; padding: 9px 12px; text-align: right; vertical-align: top; }
    table.details th { background: #f2f5f9; width: 190px; font-weight: 700; white-space: nowrap; }

    .reject-box {
        border: 1.5px solid #c0392b; background: #fdecea; color: #7b241c;
        border-radius: 8px; padding: 14px 16px; margin: 16px 0; font-size: 15px;
    }
    .reject-box .h { font-weight: 700; margin-bottom: 6px; font-size: 16px; }
    .cancel-box {
        border: 1.5px solid #7f8c8d; background: #f4f6f6; color: #4d5656;
        border-radius: 8px; padding: 14px 16px; margin: 16px 0; font-size: 15px;
    }
    .stamp {
        display: inline-block; margin-top: 10px; padding: 6px 14px; border-radius: 6px;
        font-weight: 700; font-size: 14px;
    }
    .stamp.ok { background: #eafaf1; color: #1e8449; border: 1px solid #58d68d; }
    .stamp.no { background: #fdecea; color: #c0392b; border: 1px solid #e6b0aa; }

    .signatures { display: flex; justify-content: space-between; gap: 20px; margin-top: 50px; font-size: 14.5px; }
    .signatures .sig { text-align: center; flex: 1; }
    .signatures .sig .line { margin-top: 40px; border-top: 1px solid #333; padding-top: 6px; }

    .foot { margin-top: 20px; text-align: center; color: #888; font-size: 11.5px; }

    .empty-note { text-align: center; padding: 60px 20px; color: #666; font-size: 16px; }

    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .sheet {
            width: auto; min-height: auto; margin: 0; padding: 8mm 10mm; box-shadow: none;
            /* one request per page */
            page-break-after: always; break-after: page;
        }
        .sheet:last-of-type { page-break-after: auto; break-after: auto; }
        table.details, .signatures { page-break-inside: avoid; break-inside: avoid; }
        @page { size: A4; margin: 10mm; }
    }
</style>
