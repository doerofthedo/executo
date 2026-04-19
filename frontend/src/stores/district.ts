import { defineStore } from 'pinia';

interface CurrentDistrictState {
    currentDistrictUlid: string | null;
}

export const useDistrictStore = defineStore('district', {
    state: (): CurrentDistrictState => ({
        currentDistrictUlid: null,
    }),
    actions: {
        setCurrentDistrict(ulid: string): void {
            this.currentDistrictUlid = ulid;
        },
    },
});
