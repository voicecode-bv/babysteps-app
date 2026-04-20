export interface CommentUser {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
}

export interface Comment {
    id: number;
    parent_comment_id: number | null;
    body: string;
    created_at: string;
    user: CommentUser;
    likes_count: number;
    is_liked: boolean;
    replies: Comment[];
}
