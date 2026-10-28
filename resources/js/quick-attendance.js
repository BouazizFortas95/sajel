import {
    attendanceQueueKey,
    attendanceRosterKey,
    prepareAttendanceStorage,
} from './attendance-storage';

window.sajelQuickAttendance = function (config) {
    return {
        online: navigator.onLine,
        syncing: false,
        syncError: false,
        syncMessage: '',
        schedules: config.schedules,
        snapshot: config.snapshot,
        roster: config.snapshot?.students ?? [],
        selectedScheduleId: config.snapshot?.schedule_id ?? null,
        date: config.snapshot?.date ?? config.date,
        queue: {},
        labels: config.labels,
        userKey: String(config.userKey ?? ''),
        queueKey: 'sajel.attendance.queue',

        init() {
            this.initializeStorage();
            this.readQueue();
            this.restoreRoster();
            this.listenForConnection();

            window.addEventListener('attendance-roster-updated', (event) => {
                this.setSnapshot(event.detail.snapshot);
            });

            if (this.online && this.queuedCount > 0) {
                this.flushQueue();
            }
        },

        get queuedCount() {
            return Object.keys(this.queue).length;
        },

        get activeSchedule() {
            return this.schedules.find((schedule) => schedule.id === Number(this.selectedScheduleId));
        },

        initializeStorage() {
            if (! this.userKey) {
                return;
            }

            // Never fall back to the legacy unscoped queue when the owner
            // marker cannot be written; the scoped key is authoritative.
            this.queueKey = attendanceQueueKey(this.userKey);

            try {
                prepareAttendanceStorage(window.localStorage, this.userKey);
            } catch (error) {
                console.error('Unable to prepare the offline attendance storage.', error);
            }
        },

        rosterStorageKey(scheduleId, date) {
            if (! this.userKey) {
                return `sajel.attendance.roster.${scheduleId}.${date}`;
            }

            return attendanceRosterKey(this.userKey, scheduleId, date);
        },

        readQueue() {
            try {
                const storedQueue = JSON.parse(localStorage.getItem(this.queueKey) || '{}');

                this.queue = storedQueue && typeof storedQueue === 'object' && ! Array.isArray(storedQueue)
                    ? storedQueue
                    : {};
            } catch (error) {
                console.error('Unable to read the offline attendance queue.', error);
                this.queue = {};
            }
        },

        writeQueue() {
            try {
                localStorage.setItem(this.queueKey, JSON.stringify(this.queue));
            } catch (error) {
                console.error('Unable to save offline attendance changes.', error);
                this.syncError = true;
                this.syncMessage = this.labels.storageError;
            }
        },

        persistSnapshot() {
            if (!this.snapshot?.schedule_id || !this.snapshot?.date) {
                return;
            }

            this.snapshot.students = this.roster;

            try {
                localStorage.setItem(
                    this.rosterStorageKey(this.snapshot.schedule_id, this.snapshot.date),
                    JSON.stringify(this.snapshot),
                );
            } catch (error) {
                console.error('Unable to cache the offline attendance roster.', error);
                this.syncError = true;
                this.syncMessage = this.labels.storageError;
            }
        },

        restoreRoster() {
            if (! this.online && this.selectedScheduleId && this.date) {
                try {
                    const cached = localStorage.getItem(this.rosterStorageKey(this.selectedScheduleId, this.date));

                    if (cached) {
                        this.setSnapshot(JSON.parse(cached), false);
                    }
                } catch (error) {
                    console.error('Unable to restore the offline attendance roster.', error);
                    this.syncError = true;
                    this.syncMessage = this.labels.storageError;
                }
            }

            this.applyQueuedChanges();

            // Cache the freshly loaded roster immediately, not only after the
            // first edit, so it survives an offline refresh.
            this.persistSnapshot();
        },

        setSnapshot(snapshot, persist = true) {
            if (!snapshot) {
                return;
            }

            this.snapshot = snapshot;
            this.selectedScheduleId = Number(snapshot.schedule_id);
            this.date = snapshot.date;
            this.roster = snapshot.students ?? [];
            this.applyQueuedChanges();

            if (persist) {
                this.persistSnapshot();
            }
        },

        applyQueuedChanges() {
            if (!this.snapshot) {
                return;
            }

            this.roster = this.roster.map((student) => {
                const key = `${this.snapshot.schedule_id}:${this.snapshot.date}:${student.student_id}`;
                const queued = this.queue[key];

                return queued
                    ? { ...student, status: queued.status, remarks: queued.remarks ?? '' }
                    : student;
            });
        },

        listenForConnection() {
            window.addEventListener('online', () => {
                this.online = true;

                // Flush the queued entries first, then realign Livewire's
                // selected session with the schedule chosen while offline.
                this.flushQueue().then(() => this.syncSelectedSchedule());
            });

            window.addEventListener('offline', () => {
                this.online = false;
            });
        },

        async syncSelectedSchedule() {
            if (! this.selectedScheduleId) {
                return;
            }

            try {
                await this.$wire.selectSchedule(Number(this.selectedScheduleId));
            } catch (error) {
                console.error('Unable to refresh the attendance roster after reconnecting.', error);
            }
        },

        selectSchedule(scheduleId) {
            const normalizedScheduleId = Number(scheduleId);
            const previousScheduleId = this.snapshot?.schedule_id ?? null;

            this.selectedScheduleId = normalizedScheduleId;

            if (this.online) {
                this.$wire.selectSchedule(normalizedScheduleId).catch((error) => {
                    console.error('Unable to load the selected attendance roster.', error);
                    this.selectedScheduleId = previousScheduleId;
                });

                return;
            }

            try {
                const savedRoster = localStorage.getItem(
                    this.rosterStorageKey(normalizedScheduleId, this.date),
                );

                if (savedRoster) {
                    this.setSnapshot(JSON.parse(savedRoster), false);
                } else {
                    this.snapshot = null;
                    this.roster = [];
                }
            } catch (error) {
                console.error('Unable to read the selected offline attendance roster.', error);
                this.snapshot = null;
                this.roster = [];
                this.syncError = true;
                this.syncMessage = this.labels.storageError;
            }
        },

        queueStudent(student, status = student.status, remarks = student.remarks ?? '') {
            if (!this.snapshot) {
                return;
            }

            student.status = status;
            student.remarks = remarks;

            const key = `${this.snapshot.schedule_id}:${this.snapshot.date}:${student.student_id}`;
            this.queue[key] = {
                schedule_id: this.snapshot.schedule_id,
                date: this.snapshot.date,
                student_id: student.student_id,
                status,
                remarks,
            };

            this.writeQueue();
            this.persistSnapshot();

            if (this.online) {
                clearTimeout(this.syncTimer);
                this.syncTimer = setTimeout(() => this.flushQueue(), 250);
            }
        },

        markAllPresent() {
            this.roster.forEach((student) => this.queueStudent(student, 'present'));
        },

        async flushQueue() {
            if (!this.online || this.syncing || this.queuedCount === 0) {
                return;
            }

            this.syncing = true;
            this.syncError = false;
            this.syncMessage = '';

            try {
                let syncedTotal = 0;

                while (this.online && this.queuedCount > 0) {
                    const sent = Object.fromEntries(
                        Object.entries(this.queue).slice(0, 200).map(([key, entry]) => [
                            key,
                            JSON.parse(JSON.stringify(entry)),
                        ]),
                    );
                    const result = await this.$wire.syncOfflineAttendance(Object.values(sent));
                    const synced = Number(result?.synced ?? 0);

                    if (! Number.isFinite(synced) || synced < 0) {
                        throw new Error('The synchronization response was not valid.');
                    }

                    Object.entries(sent).forEach(([key, entry]) => {
                        if (JSON.stringify(this.queue[key]) === JSON.stringify(entry)) {
                            delete this.queue[key];
                        }
                    });

                    syncedTotal += synced;
                    this.writeQueue();
                    this.persistSnapshot();
                }

                this.syncMessage = this.labels.synced.replace(':count', syncedTotal);
            } catch (error) {
                console.error('Offline attendance synchronization failed.', error);
                this.syncError = true;
                this.syncMessage = this.labels.syncError;
            } finally {
                this.syncing = false;
            }
        },

        async saveAttendance() {
            await this.flushQueue();

            if (!this.online || this.queuedCount > 0) {
                return;
            }

            this.$wire.saveAttendance();
        },

        statusClass(status) {
            return {
                present: 'text-emerald-700 dark:text-emerald-400',
                absent: 'text-rose-700 dark:text-rose-400',
                late: 'text-amber-700 dark:text-amber-400',
                excused: 'text-sky-700 dark:text-sky-400',
            }[status] ?? 'text-gray-500';
        },

        statusButtonClass(studentStatus, status) {
            if (studentStatus !== status) {
                return 'border-transparent text-gray-400 hover:border-gray-300 dark:text-gray-500 dark:hover:border-gray-600';
            }

            return {
                present: 'border-emerald-500 bg-emerald-100 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-900/30 dark:text-emerald-400',
                absent: 'border-rose-500 bg-rose-100 text-rose-700 dark:border-rose-400 dark:bg-rose-900/30 dark:text-rose-400',
                late: 'border-amber-500 bg-amber-100 text-amber-700 dark:border-amber-400 dark:bg-amber-900/30 dark:text-amber-400',
                excused: 'border-sky-500 bg-sky-100 text-sky-700 dark:border-sky-400 dark:bg-sky-900/30 dark:text-sky-400',
            }[status];
        },
    };
};
