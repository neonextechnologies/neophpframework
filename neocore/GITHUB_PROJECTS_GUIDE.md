# GitHub Projects Import Guide

วิธีนำ Backlog ไปใช้ใน GitHub Projects

## 🎯 วิธีที่ 1: ใช้ GitHub CLI (แนะนำ - Automated)

**⚠️ หมายเหตุ:** GitHub Projects ไม่มีฟีเจอร์ import CSV โดยตรง ต้องใช้ CLI/API

### ขั้นตอน:

1. **Install GitHub CLI**
   ```powershell
   # Windows
   winget install --id GitHub.cli
   
   # หรือ download: https://cli.github.com/
   ```

2. **Login**
   ```bash
   gh auth login
   ```

3. **สร้าง Labels**
   ```powershell
   .\scripts\create-labels.ps1
   ```

4. **สร้าง Issues จาก CSV**
   ```bash
   # แก้ไข $dryRun = false ใน script
   php scripts/create-github-issues.php
   ```

5. **สร้าง Project**
   - ไปที่ Repository → Projects → New project
   - เลือก "Table" template
   - ตั้งชื่อ: "NeoCore Development Backlog"
   
6. **เพิ่ม Issues เข้า Project**
   - ใน Project → Add items → เลือก Issues ที่สร้าง

---

## 🔧 วิธีที่ 2: Manual Copy-Paste (ถ้าไม่อยากใช้ CLI)

### ขั้นตอน:

1. **สร้าง GitHub Project** (Table view)
2. **เพิ่ม Custom Fields:**
   - Priority (Single select): Critical, High, Medium, Low
   - Phase (Single select): Phase 1-10
   - Story Points (Number)
   - Epic (Text)
3. **Copy tasks จาก BACKLOG.md** → สร้าง Issues ทีละอันใน GitHub
4. **เพิ่ม Issues เข้า Project** → ตั้งค่า fields

**หมายเหตุ:** วิธีนี้ใช้เวลานาน แต่ไม่ต้องติดตั้งอะไร

---

## � วิธีที่ 3: ใช้ GitHub API (Advanced)

### ขั้นตอน:

1. **สร้าง Personal Access Token**
   - GitHub → Settings → Developer settings → Personal access tokens
   - Generate new token (classic)
   - เลือก scopes: `repo`, `project`

2. **ตั้งค่า environment variable**
   ```powershell
   $env:GITHUB_TOKEN = "your_token_here"
   $env:GITHUB_OWNER = "neonextechnologies"
   $env:GITHUB_REPO = "neophpframework"
   ```

3. **Run script**
   ```bash
   php scripts/create-github-issues.php
   ```

---

## 📋 Manual Method (สำหรับ tasks น้อยๆ)

1. ไปที่ Repository → Issues
2. สร้าง Issue ใหม่ตาม Task ID
3. เพิ่ม Labels: `priority:critical`, `phase-1`, etc.
4. เชื่อม Issue กับ Project

---

## 🏷️ Label Recommendations

สร้าง Labels เหล่านี้ใน Repository:

**Priority:**
- `priority:critical` (🔴 red)
- `priority:high` (🟡 yellow)
- `priority:medium` (🟢 green)
- `priority:low` (🔵 blue)

**Phase:**
- `phase-1-auth` (purple)
- `phase-2-storage` (pink)
- `phase-3-cache` (orange)
- etc.

**Type:**
- `type:feature` (green)
- `type:bug` (red)
- `type:docs` (blue)
- `type:enhancement` (yellow)

**Epic:**
- `epic:authentication`
- `epic:file-storage`
- `epic:caching`
- etc.

---

## 📊 Project Views แนะนำ

### View 1: Board by Status
- Columns: Planned → In Progress → Done
- Group by: Status

### View 2: Table by Phase
- Group by: Phase
- Sort by: Priority
- Filter: Show on (แนะนำสำหรับผู้เริ่มต้น)

### เริ่มต้นง่ายๆ 3 ขั้นตอน:

**ขั้นตอนที่ 1: Install GitHub CLI**
```powershell
winget install --id GitHub.cli
gh auth login
```

**ขั้นตอนที่ 2: สร้าง Labels และ Issues**
```powershell
# สร้าง Labels
.\scripts\create-labels.ps1

# สร้าง Issues (แก้ไข $dryRun = false ในไฟล์ก่อน)
php scripts\create-github-issues.php
```

**ขั้นตอนที่ 3: สร้าง Project**
1. ไปที่ GitHub Repository → Projects → New project
2. เลือก **"Team backlog"** template
3. คลิก **"+ Add items"** → เลือก Issues ที่สร้าง
4. Done! 🎉

---

## ⚡ Alternative: สร้างทีละน้อย

หากไม่อยากสร้าง 90+ issues ทีเดียว:

1. **เลือก Phase ที่จะทำก่อน** (เช่น Phase 1: Authentication)
2. **สร้างแค่ Epic นั้นๆ** (8-13 tasks)
3. **เริ่มทำงาน** → สร้าง tasks phase ถัดไปภายหลัง

```powershell
# แก้ไข script ให้สร้างแค่ Phase 1
# ในไฟล์ create-github-issues.php เพิ่ม filter:
# if (strpos($task['Phase'], 'Phase 1') === false) continue;
**Option B: ใช้ Script (10 นาที)**
```bash
# 1. Install GitHub CLI
gh auth login

# 2. Run script
php scripts/create-github-issues.php

# 3. Link issues to project
```

---

## 💡 Best Practices

1. **ใช้ Milestones** - สร้าง Milestone สำหรับแต่ละ Phase
2. **ใช้ Labels** - Tag ทุก Issue ด้วย priority และ phase
3. **Link Issues** - เชื่อม related issues เข้าด้วยกัน
4. **Update Status** - อัปเดตสถานะเป็นประจำ
5. **Use Assignees** - มอบหมายงานให้ developer

---

## 📝 Next Steps

1. สร้าง GitHub Project
2. Import `backlog.csv`
3. Setup custom fields
4. Add team members
5. Start working! 🚀
