.id-preview-shell { width: min(100%, 560px); margin-inline: auto; }
.id-preview-heading { margin-bottom: 1rem; }
.tala-id-card {
  box-sizing: border-box;
  width: 85.6mm;
  height: 53.98mm;
  border: 1px solid #d7ddd9;
  border-radius: 3mm;
  background: #fff;
  color: #18221d;
  font-family: Arial, Helvetica, sans-serif;
}
.tala-id-header {
  height: 9mm;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2mm;
  padding: 1.2mm 3mm;
  background: #151515;
  color: #fff;
}
.tala-id-brand { display: flex; align-items: center; gap: 1.5mm; min-width: 0; }
.tala-id-logo { display: block; width: 6mm; height: 6mm; object-fit: contain; }
.tala-id-location { font-size: 5.5pt; line-height: 1.1; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
.tala-id-title { font-size: 9pt; font-weight: 700; line-height: 1.2; }
.tala-id-status { flex: none; padding: 1mm 2mm; font-size: 6pt; }
.tala-id-content {
  height: 38mm;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 17mm minmax(0, 1fr) 25mm;
  align-items: center;
  gap: 2mm;
  padding: 2mm 3mm;
}
.tala-id-avatar {
  width: 17mm;
  height: 20mm;
  border: .3mm solid #d7ddd9;
  border-radius: 1.5mm;
  object-fit: cover;
}
.tala-id-information { min-width: 0; overflow: hidden; }
.tala-id-name {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow-wrap: anywhere;
  font-size: 9pt;
  font-weight: 700;
  line-height: 1.08;
}
.tala-id-detail { margin-top: 1mm; color: #424b46; font-size: 6pt; }
.tala-id-detail span { margin-right: 1mm; color: #6c757d; text-transform: uppercase; }
.tala-id-unique { margin-top: 1mm; color: #176b52; font-family: monospace; font-size: 8pt; font-weight: 700; white-space: nowrap; }
.tala-id-address {
  display: -webkit-box;
  overflow: hidden;
  margin-top: 1mm;
  color: #424b46;
  font-size: 5.8pt;
  line-height: 1.15;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}
.tala-id-qr { width: 25mm; height: 25mm; }
.tala-id-qr svg { display: block; width: 25mm; height: 25mm; }
.tala-id-footer {
  box-sizing: border-box;
  height: 6.98mm;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1mm;
  padding: 1mm 3mm;
  background: #eef2ef;
  color: #52605a;
  font-size: 5.5pt;
}
@media screen and (max-width: 575.98px) {
  .id-preview-shell { width: 100%; }
  .tala-id-card { width: min(85.6mm, calc(100vw - 2rem)); height: auto; aspect-ratio: 85.6 / 53.98; }
}
