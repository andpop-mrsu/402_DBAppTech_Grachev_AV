class ApiService {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
    }

    async createGame(playerName, fieldSize, minesCount, minePositions) {
        try {
            const response = await fetch(`${this.baseUrl}/games`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    player_name: playerName,
                    field_size: fieldSize,
                    mines_count: minesCount,
                    mine_positions: minePositions
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error creating game:', error);
            throw error;
        }
    }

    async saveMove(gameId, moveData) {
        try {
            const response = await fetch(`${this.baseUrl}/step/${gameId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(moveData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error saving move:', error);
            throw error;
        }
    }

    async getAllGames() {
        try {
            const response = await fetch(`${this.baseUrl}/games`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error getting all games:', error);
            throw error;
        }
    }

    async getGameById(gameId) {
        try {
            const response = await fetch(`${this.baseUrl}/games/${gameId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error getting game by ID:', error);
            throw error;
        }
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiService;
}

export { ApiService };
