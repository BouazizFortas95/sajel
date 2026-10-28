import assert from 'node:assert/strict';
import test from 'node:test';

import {
    ATTENDANCE_OWNER_KEY,
    attendanceQueueKey,
    attendanceRosterKey,
    prepareAttendanceStorage,
    purgeGuestAttendanceData,
} from '../../resources/js/attendance-storage.js';

class MemoryStorage {
    #data = new Map();

    get length() {
        return this.#data.size;
    }

    key(index) {
        return [...this.#data.keys()][index] ?? null;
    }

    getItem(key) {
        return this.#data.has(key) ? this.#data.get(key) : null;
    }

    setItem(key, value) {
        this.#data.set(key, String(value));
    }

    removeItem(key) {
        this.#data.delete(key);
    }
}

function storageWith(entries) {
    const storage = new MemoryStorage();

    Object.entries(entries).forEach(([key, value]) => storage.setItem(key, value));

    return storage;
}

test('prepareAttendanceStorage binds storage to the first signed-in user', () => {
    const storage = storageWith({ 'sajel.locale': 'ar' });
    const result = prepareAttendanceStorage(storage, 7);

    assert.equal(result.ownerChanged, true);
    assert.equal(result.clearedKeys, 0);
    assert.equal(storage.getItem(ATTENDANCE_OWNER_KEY), '7');
    assert.equal(storage.getItem('sajel.locale'), 'ar');
});

test('prepareAttendanceStorage keeps the current user queue and roster', () => {
    const queueKey = attendanceQueueKey(7);
    const rosterKey = attendanceRosterKey(7, 3, '2026-10-28');
    const storage = storageWith({
        [ATTENDANCE_OWNER_KEY]: '7',
        [queueKey]: '{"pending":true}',
        [rosterKey]: '{"students":[]}',
    });

    const result = prepareAttendanceStorage(storage, '7');

    assert.equal(result.ownerChanged, false);
    assert.equal(result.clearedKeys, 0);
    assert.equal(storage.getItem(queueKey), '{"pending":true}');
    assert.equal(storage.getItem(rosterKey), '{"students":[]}');
});

test('prepareAttendanceStorage removes another user and legacy attendance data only', () => {
    const oldQueue = attendanceQueueKey(7);
    const oldRoster = attendanceRosterKey(7, 3, '2026-10-28');
    const storage = storageWith({
        [ATTENDANCE_OWNER_KEY]: '7',
        [oldQueue]: '{"pending":true}',
        [oldRoster]: '{"students":[]}',
        'sajel.attendance.queue': '{"legacy":true}',
        'sajel.attendance.roster.3.2026-10-28': '{"legacy":true}',
        'sajel.locale': 'ar',
    });

    const result = prepareAttendanceStorage(storage, 8);

    assert.equal(result.ownerChanged, true);
    assert.equal(result.clearedKeys, 5);
    assert.equal(storage.getItem(ATTENDANCE_OWNER_KEY), '8');
    assert.equal(storage.getItem(oldQueue), null);
    assert.equal(storage.getItem(oldRoster), null);
    assert.equal(storage.getItem('sajel.attendance.queue'), null);
    assert.equal(storage.getItem('sajel.attendance.roster.3.2026-10-28'), null);
    assert.equal(storage.getItem('sajel.locale'), 'ar');
});

test('prepareAttendanceStorage ignores a missing user key', () => {
    const storage = storageWith({ [ATTENDANCE_OWNER_KEY]: '7' });

    const result = prepareAttendanceStorage(storage, '');

    assert.equal(result.ownerChanged, false);
    assert.equal(storage.getItem(ATTENDANCE_OWNER_KEY), '7');
});

test('purgeGuestAttendanceData keeps an unsynced queue but removes names', () => {
    const queueKey = attendanceQueueKey(7);
    const rosterKey = attendanceRosterKey(7, 3, '2026-10-28');
    const storage = storageWith({
        [ATTENDANCE_OWNER_KEY]: '7',
        [queueKey]: '{"pending":true}',
        [rosterKey]: '{"students":[]}',
        'sajel.locale': 'ar',
    });

    const result = purgeGuestAttendanceData(storage);

    assert.equal(result.preservedQueue, true);
    assert.equal(result.removedKeys, 1);
    assert.equal(storage.getItem(queueKey), '{"pending":true}');
    assert.equal(storage.getItem(ATTENDANCE_OWNER_KEY), '7');
    assert.equal(storage.getItem(rosterKey), null);
    assert.equal(storage.getItem('sajel.locale'), 'ar');
});

test('purgeGuestAttendanceData clears all attendance data when nothing is queued', () => {
    const rosterKey = attendanceRosterKey(7, 3, '2026-10-28');
    const storage = storageWith({
        [ATTENDANCE_OWNER_KEY]: '7',
        [rosterKey]: '{"students":[]}',
        'sajel.locale': 'ar',
    });

    const result = purgeGuestAttendanceData(storage);

    assert.equal(result.preservedQueue, false);
    assert.equal(result.removedKeys, 2);
    assert.equal(storage.getItem(ATTENDANCE_OWNER_KEY), null);
    assert.equal(storage.getItem(rosterKey), null);
    assert.equal(storage.getItem('sajel.locale'), 'ar');
});

test('purgeGuestAttendanceData recognises legacy queue and roster keys', () => {
    const storage = storageWith({
        'sajel.attendance.queue': '{"legacy":true}',
        'sajel.attendance.roster.3.2026-10-28': '{"legacy":true}',
    });

    const result = purgeGuestAttendanceData(storage);

    assert.equal(result.preservedQueue, true);
    assert.equal(result.removedKeys, 1);
    assert.equal(storage.getItem('sajel.attendance.queue'), '{"legacy":true}');
    assert.equal(storage.getItem('sajel.attendance.roster.3.2026-10-28'), null);
});

test('attendance storage keys are scoped by user', () => {
    assert.equal(attendanceQueueKey(7), 'sajel.attendance.7.queue');
    assert.equal(
        attendanceRosterKey(7, 12, '2026-10-28'),
        'sajel.attendance.7.roster.12.2026-10-28',
    );
});
