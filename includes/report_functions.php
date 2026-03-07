<!-- Include XLSX Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Export table to Excel
function exportReportToExcel(tableId, reportTitle, filename) {
    if (typeof XLSX === 'undefined') {
        alert('Loading export library, please try again...');
        return;
    }
    
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.book_new();
    const data = [];
    
    // Add headers
    data.push(['PASSI CITY - DRRMO']);
    data.push([reportTitle]);
    data.push(['Generated: ' + new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })]);
    data.push([]);
    
    // Get table headers
    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        const headers = [];
        headerRow.querySelectorAll('th').forEach(th => headers.push(th.textContent.trim()));
        data.push(headers);
    }
    
    // Get table data
    const tbody = table.querySelector('tbody');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach(cell => {
                let text = cell.textContent.trim().replace(/\s+/g, ' ');
                if (text.startsWith('₱')) {
                    rowData.push(parseFloat(text.replace('₱', '').replace(/,/g, '')) || 0);
                } else {
                    rowData.push(text);
                }
            });
            if (rowData.some(cell => cell !== '')) data.push(rowData);
        });
    }
    
    const ws = XLSX.utils.aoa_to_sheet(data);
    ws['!cols'] = Array(20).fill({wch: 15});
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, filename);
}

// Professional print
function printProfessionalReport(tableId, reportTitle, subtitle) {
    const table = document.getElementById(tableId);
    const printWindow = window.open('', '', 'width=900,height=700');
    
    printWindow.document.write(`
    <html><head><title>${reportTitle}</title>
    <style>
        @page { size: A4 landscape; margin: 0.5in; }
        body { font-family: Arial; margin: 20px; color: #000; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 15px; }
        .title { font-size: 16pt; font-weight: bold; margin: 3px 0; }
        .subtitle { font-size: 11pt; margin: 2px 0; }
        .doc-title { font-size: 14pt; font-weight: bold; margin: 10px 0 5px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9pt; }
        th, td { border: 1px solid #000; padding: 6px 4px; }
        th { background: #fff; font-weight: bold; text-align: center; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sig-box { width: 30%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 35px; padding-top: 5px; font-weight: bold; }
    </style>
    </head><body>
    <div class="header">
        <div class="title">Republic of the Philippines</div>
        <div class="subtitle">Province of Iloilo</div>
        <div class="title">CITY OF PASSI</div>
        <div class="subtitle">City Disaster Risk Reduction and Management Office</div>
        <div class="doc-title">${reportTitle}</div>
        ${subtitle ? '<div style="font-size: 9pt; margin-top: 5px;">' + subtitle + '</div>' : ''}
        <div style="font-size: 9pt; margin-top: 5px;">Generated: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
    </div>
    <table>`);
    
    // Headers
    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        printWindow.document.write('<thead><tr>');
        headerRow.querySelectorAll('th').forEach(th => {
            const text = th.textContent.trim();
            if (!text.match(/action/i)) {
                printWindow.document.write(`<th>${text}</th>`);
            }
        });
        printWindow.document.write('</tr></thead>');
    }
    
    // Body
    printWindow.document.write('<tbody>');
    const tbody = table.querySelector('tbody');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const hasAction = cells.some(cell => 
                cell.textContent.includes('Restock') || 
                cell.querySelector('.btn') ||
                cell.textContent.includes('Edit')
            );
            
            printWindow.document.write('<tr>');
            cells.forEach((cell, i) => {
                const text = cell.textContent.trim();
                if (!text.includes('Restock') && !cell.querySelector('.btn') && !text.includes('Edit')) {
                    const align = text.match(/^₱[\d,\.]+$/) ? 'right' : (text.match(/^\d+$/) ? 'center' : 'left');
                    printWindow.document.write(`<td style="text-align: ${align}">${text}</td>`);
                }
            });
            printWindow.document.write('</tr>');
        });
    }
    
    printWindow.document.write(`
    </tbody></table>
    <div class="signatures">
        <div class="sig-box"><div class="sig-line">&nbsp;</div><div style="font-size: 9pt; margin-top: 3px;">Prepared By</div></div>
        <div class="sig-box"><div class="sig-line">&nbsp;</div><div style="font-size: 9pt; margin-top: 3px;">Reviewed By</div></div>
        <div class="sig-box"><div class="sig-line">&nbsp;</div><div style="font-size: 9pt; margin-top: 3px;">Approved By</div></div>
    </div>
    </body></html>`);
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 250);
}
</script>