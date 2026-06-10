// types/index.d.ts (o donde tengas tus tipos)
export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[]; // Asegúrate de que coincida con lo que viene del backend
    // Agrega aquí cualquier otro campo del usuario
}

export interface SharedProps {
    auth: {
        user: User;
    };
    // Agrega otras propiedades compartidas comunes si las tienes
}