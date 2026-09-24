import sqlite3
from pathlib import Path
import shutil

DB_PATH = Path(r"C:\Users\Meg Rhyan\AppData\Local\MTCGS-EMS\attendance_local_v2.db")
BACKUP_PATH = DB_PATH.with_suffix(".db.bak")

if not DB_PATH.exists():
    raise FileNotFoundError(f"Database not found: {DB_PATH}")

shutil.copy2(DB_PATH, BACKUP_PATH)
print(f"Backup created: {BACKUP_PATH}")

conn = sqlite3.connect(str(DB_PATH))
cur = conn.cursor()

cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='Attendance'")
if cur.fetchone() is None:
    raise RuntimeError("Attendance table not found")

cols = [row[1] for row in cur.execute("PRAGMA table_info(Attendance)").fetchall()]
print("Before:", cols)

if "LaptopMacAddress" in cols:
    cur.execute("ALTER TABLE Attendance RENAME TO Attendance_old")
    new_cols = [
        "Id INTEGER PRIMARY KEY AUTOINCREMENT",
        "EmployeeID TEXT",
        "Name TEXT",
        "Branch TEXT",
        "Position TEXT",
        "Action TEXT",
        "Time TEXT",
        "Date TEXT",
        "MacAddress TEXT",
        "FingerprintData TEXT",
        "IsSynced INTEGER DEFAULT 0",
        "ScannerSerial TEXT",
        "WifiMacAddress TEXT",
        "DeviceId TEXT",
        "Location TEXT",
    ]
    cur.execute(f"CREATE TABLE Attendance ({', '.join(new_cols)})")
    insert_sql = """
        INSERT INTO Attendance (
            Id, EmployeeID, Name, Branch, Position, Action, Time, Date,
            MacAddress, FingerprintData, IsSynced,
            ScannerSerial, WifiMacAddress, DeviceId, Location
        )
        SELECT
            Id, EmployeeID, Name, Branch, Position, Action, Time, Date,
            MacAddress, FingerprintData, IsSynced,
            ScannerSerial, WifiMacAddress, DeviceId,
            COALESCE(NULLIF(TRIM(Location), ''), 'Main Office')
        FROM Attendance_old
    """
    cur.execute(insert_sql)
    cur.execute("DROP TABLE Attendance_old")

cur.execute("UPDATE Attendance SET Location = 'Main Office' WHERE COALESCE(TRIM(Location), '') = ''")
conn.commit()

final_cols = [row[1] for row in cur.execute("PRAGMA table_info(Attendance)").fetchall()]
print("After:", final_cols)
print("Rows:", cur.execute("SELECT COUNT(*) FROM Attendance").fetchone()[0])

conn.close()
