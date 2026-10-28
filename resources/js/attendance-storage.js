export const ATTENDANCE_STORAGE_PREFIX = 'sajel.attendance.';
export const ATTENDANCE_OWNER_KEY = `${ATTENDANCE_STORAGE_PREFIX}owner`;

/**
 * Return every attendance-related localStorage key without mutating the
 * storage while it is being enumerated.
 *
 * @param {Storage} storage
 * @returns {string[]}
 */
function attendanceKeys(storage) {
    const keys = [];

    for (let index = 0; index < storage.length; index += 1) {
        const key = storage.key(index);

        if (key && key.startsWith(ATTENDANCE_STORAGE_PREFIX)) {
            keys.push(key);
        }
    }

    return keys;
}

function isQueueKey(key) {
    return key.endsWith('.queue');
}

function isRosterKey(key) {
    return key.includes('.roster.');
}

/**
 * Bind the browser's offline attendance data to the signed-in user.
 *
 * A different user, or legacy unscoped data, is removed before the queue and
 * roster are read. Keys belonging to other applications are untouched.
 *
 * @param {Storage} storage
 * @param {string|number|null|undefined} userKey
 * @returns {{ownerChanged: boolean, clearedKeys: number}}
 */
export function prepareAttendanceStorage(storage, userKey) {
    const normalizedUserKey = String(userKey ?? '').trim();

    if (normalizedUserKey === '') {
        return { ownerChanged: false, clearedKeys: 0 };
    }

    if (storage.getItem(ATTENDANCE_OWNER_KEY) === normalizedUserKey) {
        return { ownerChanged: false, clearedKeys: 0 };
    }

    const keys = attendanceKeys(storage);

    keys.forEach((key) => storage.removeItem(key));
    storage.setItem(ATTENDANCE_OWNER_KEY, normalizedUserKey);

    return { ownerChanged: true, clearedKeys: keys.length };
}

/**
 * Remove student-identifying attendance data when a guest reaches the app
 * (for example the login screen after logout).
 *
 * Rosters contain names and are always removed. A pending queue is kept so a
 * teacher who signs back in does not lose unsynced attendance; it contains
 * schedule/student ids and remarks, and is removed as soon as another user
 * opens Quick Attendance.
 *
 * @param {Storage} storage
 * @returns {{preservedQueue: boolean, removedKeys: number}}
 */
export function purgeGuestAttendanceData(storage) {
    const keys = attendanceKeys(storage);
    const preservedQueue = keys.some(isQueueKey);
    let removedKeys = 0;

    keys.forEach((key) => {
        if (! preservedQueue || isRosterKey(key)) {
            storage.removeItem(key);
            removedKeys += 1;
        }
    });

    return { preservedQueue, removedKeys };
}

/**
 * @param {string|number} userKey
 * @returns {string}
 */
export function attendanceQueueKey(userKey) {
    return `${ATTENDANCE_STORAGE_PREFIX}${String(userKey).trim()}.queue`;
}

/**
 * @param {string|number} userKey
 * @param {string|number} scheduleId
 * @param {string} date
 * @returns {string}
 */
export function attendanceRosterKey(userKey, scheduleId, date) {
    return `${ATTENDANCE_STORAGE_PREFIX}${String(userKey).trim()}.roster.${scheduleId}.${date}`;
}
